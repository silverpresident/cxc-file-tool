using cxc_tool_asp.Models;
using CsvHelper;
using CsvHelper.Configuration;
using CsvHelper.TypeConversion;
using System.Globalization;
using System.Text.RegularExpressions;

namespace cxc_tool_asp.Services;

/// <summary>
/// Service implementation for managing subject data stored in subjects.csv.
/// </summary>
public class SubjectService : ISubjectService
{
    private readonly string _subjectFilePath;
    private static readonly SemaphoreSlim _fileLock = new(1, 1); // Lock for file access
    private readonly ILogger<SubjectService> _logger;

    // Basic configuration for CsvHelper
    private readonly CsvConfiguration _csvConfig = new(CultureInfo.InvariantCulture)
    {
        HasHeaderRecord = true,
        MissingFieldFound = null, // Ignore missing fields
        HeaderValidated = null, // Ignore header validation
    };

    public SubjectService(IWebHostEnvironment env, ILogger<SubjectService> logger)
    {
        // Store subjects in Data2 folder like users
        var data2FolderPath = Path.Combine(env.ContentRootPath, "Data2");
        Directory.CreateDirectory(data2FolderPath); // Ensure directory exists
        _subjectFilePath = Path.Combine(data2FolderPath, "subjects.csv");
        _logger = logger;
    }

    public string GetSubjectFilePath() => _subjectFilePath;

    private async Task<List<Subject>> ReadSubjectsFromFileAsync()
    {
        await _fileLock.WaitAsync();
        try
        {
            if (!File.Exists(_subjectFilePath))
            {
                return new List<Subject>(); // Return empty list if file doesn't exist
            }

            using var reader = new StreamReader(_subjectFilePath);
            using var csv = new CsvReader(reader, _csvConfig);
            // Register converter for the enum
            csv.Context.TypeConverterCache.AddConverter<SubjectLevel>(new EnumConverter(typeof(SubjectLevel)));
            var subjects = csv.GetRecords<Subject>().ToList();
            _logger.LogInformation("Read {Count} subjects from {File}", subjects.Count, _subjectFilePath);
            return subjects;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error reading subjects from CSV file: {FilePath}", _subjectFilePath);
            return new List<Subject>(); // Return empty list on error
        }
        finally
        {
            _fileLock.Release();
        }
    }

    private async Task WriteSubjectsToFileAsync(IEnumerable<Subject> subjects)
    {
        await _fileLock.WaitAsync();
        try
        {
            using var writer = new StreamWriter(_subjectFilePath); // Overwrites existing file
            using var csv = new CsvWriter(writer, _csvConfig);
             // Register converter for the enum for writing
            csv.Context.TypeConverterCache.AddConverter<SubjectLevel>(new EnumConverter(typeof(SubjectLevel)));
            await csv.WriteRecordsAsync(subjects);
            _logger.LogInformation("Wrote {Count} subjects to {File}", subjects.Count(), _subjectFilePath);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error writing subjects to CSV file: {FilePath}", _subjectFilePath);
            throw; // Re-throw to indicate failure
        }
        finally
        {
            _fileLock.Release();
        }
    }

    public async Task<List<Subject>> GetAllSubjectsAsync()
    {
        return await ReadSubjectsFromFileAsync();
    }

    public async Task<Subject?> GetSubjectByCodeAsync(string subjectCode)
    {
        var subjects = await ReadSubjectsFromFileAsync();
        return subjects.FirstOrDefault(s => s.CxcSubjectCode == subjectCode);
    }

    public async Task<bool> AddSubjectAsync(Subject subject)
    {
        // Validate subject code format
        if (!Regex.IsMatch(subject.CxcSubjectCode, @"^\d{6}$"))
        {
             _logger.LogWarning("Attempted to add subject with invalid code format: {Code}", subject.CxcSubjectCode);
             return false;
        }

        var subjects = await ReadSubjectsFromFileAsync();

        // Check for duplicates
        if (subjects.Any(s => s.CxcSubjectCode == subject.CxcSubjectCode))
        {
            _logger.LogWarning("Attempted to add subject with duplicate code: {Code}", subject.CxcSubjectCode);
            return false;
        }

        subjects.Add(subject);

        try
        {
            await WriteSubjectsToFileAsync(subjects);
            _logger.LogInformation("Added subject: {Code} - {Name}", subject.CxcSubjectCode, subject.Name);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to write file after adding subject: {Code}", subject.CxcSubjectCode);
            return false;
        }
    }

    public async Task<bool> UpdateSubjectAsync(Subject subject)
    {
        // Validate subject code format
        if (!Regex.IsMatch(subject.CxcSubjectCode, @"^\d{6}$"))
        {
             _logger.LogWarning("Attempted to update subject with invalid code format: {Code}", subject.CxcSubjectCode);
             return false;
        }

        var subjects = await ReadSubjectsFromFileAsync();
        var index = subjects.FindIndex(s => s.CxcSubjectCode == subject.CxcSubjectCode);

        if (index == -1)
        {
            _logger.LogWarning("Attempted to update non-existent subject: {Code}", subject.CxcSubjectCode);
            return false; // Subject not found
        }

        subjects[index] = subject; // Replace existing record

        try
        {
            await WriteSubjectsToFileAsync(subjects);
            _logger.LogInformation("Updated subject: {Code} - {Name}", subject.CxcSubjectCode, subject.Name);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to write file after updating subject: {Code}", subject.CxcSubjectCode);
            return false;
        }
    }

    public async Task<bool> DeleteSubjectAsync(string subjectCode)
    {
        var subjects = await ReadSubjectsFromFileAsync();
        var initialCount = subjects.Count;
        subjects.RemoveAll(s => s.CxcSubjectCode == subjectCode);

        if (subjects.Count == initialCount)
        {
            _logger.LogWarning("Attempted to delete non-existent subject: {Code}", subjectCode);
            return false; // Subject not found
        }

        try
        {
            await WriteSubjectsToFileAsync(subjects);
            _logger.LogInformation("Deleted subject: {Code}", subjectCode);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to write file after deleting subject: {Code}", subjectCode);
            return false;
        }
    }

    public async Task<bool> DeleteAllSubjectsAsync()
    {
        try
        {
            // Write an empty list to the file
            await WriteSubjectsToFileAsync(new List<Subject>());
            _logger.LogInformation("Deleted all subjects from {File}", _subjectFilePath);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to delete all subjects from {File}", _subjectFilePath);
            return false;
        }
    }

    public async Task<int> ImportSubjectsFromCsvAsync(Stream stream)
    {
        List<Subject> importedSubjects = new();
        try
        {
            using var reader = new StreamReader(stream);
            var importConfig = new CsvConfiguration(CultureInfo.InvariantCulture)
            {
                HasHeaderRecord = true,
                MissingFieldFound = null,
                HeaderValidated = null,
                PrepareHeaderForMatch = args => args.Header.ToLower().Replace(" ", "").Replace("_", ""), // Normalize header
            };
            using var csv = new CsvReader(reader, importConfig);

            // Define ClassMap for flexible mapping
            csv.Context.RegisterClassMap<SubjectImportMap>();
             // Register converter for the enum
            csv.Context.TypeConverterCache.AddConverter<SubjectLevel>(new EnumConverter(typeof(SubjectLevel)));

            await foreach (var record in csv.GetRecordsAsync<Subject>())
            {
                // Basic validation
                if (record != null && !string.IsNullOrWhiteSpace(record.CxcSubjectCode) && Regex.IsMatch(record.CxcSubjectCode, @"^\d{6}$") && !string.IsNullOrWhiteSpace(record.Name))
                {
                    // Avoid duplicates within the import file
                    if (!importedSubjects.Any(s => s.CxcSubjectCode == record.CxcSubjectCode))
                    {
                        importedSubjects.Add(record);
                    }
                     else
                     {
                         _logger.LogWarning("Duplicate subject code found during import, skipping: {Code}", record.CxcSubjectCode);
                     }
                }
                else
                {
                    _logger.LogWarning("Invalid or missing data found during subject import, skipping record near line {Row}", csv.Context.Parser.Row);
                }
            }

            // Replace existing file content
            await WriteSubjectsToFileAsync(importedSubjects);
            _logger.LogInformation("Successfully imported {Count} subjects from stream.", importedSubjects.Count);
            return importedSubjects.Count;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error importing subjects from CSV stream.");
            return 0; // Indicate failure
        }
    }

     // --- Helper ClassMap for Import ---
    private sealed class SubjectImportMap : ClassMap<Subject>
    {
        public SubjectImportMap()
        {
            Map(m => m.Name).Name("name", "subjectname");
            Map(m => m.CxcSubjectCode).Name("cxcsubjectcode", "subjectcode", "code");
            Map(m => m.Level).Name("level").TypeConverter(new EnumConverter(typeof(SubjectLevel))); // Corrected: Removed generic argument
        }
    }
}
