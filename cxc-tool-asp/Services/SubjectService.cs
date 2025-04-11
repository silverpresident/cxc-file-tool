using cxc_tool_asp.Models;
using CsvHelper;
using CsvHelper.Configuration;
using CsvHelper.TypeConversion;
using System.Globalization;
using System.Text.RegularExpressions;
using Microsoft.Extensions.Logging; // Added
using System.Threading.Tasks; // Added
using System.Collections.Generic; // Added
using System.Linq; // Added
using System.IO; // Added
using System; // Added

namespace cxc_tool_asp.Services;

/// <summary>
/// Service implementation for managing subject data using IStorageService.
/// </summary>
public class SubjectService : ISubjectService
{
    private readonly IStorageService _storageService;
    private readonly string _subjectFileRelativePath; // Relative path
    private static readonly SemaphoreSlim _storageLock = new(1, 1); // Lock for storage access
    private readonly ILogger<SubjectService> _logger;

    private readonly CsvConfiguration _csvConfig = new(CultureInfo.InvariantCulture)
    {
        HasHeaderRecord = true,
        MissingFieldFound = null,
        HeaderValidated = null,
        PrepareHeaderForMatch = args => args.Header.ToLower().Replace(" ", "").Replace("_", "")
    };

    public SubjectService(IStorageService storageService, ILogger<SubjectService> logger)
    {
        _storageService = storageService;
        _logger = logger;
        string DataPrefix = _storageService.GetPrivateDataFolderName();
        _subjectFileRelativePath = $"{DataPrefix}/subjects.csv";
    }

    // Keep this method, returns relative path
    public string GetSubjectFilePath() => _subjectFileRelativePath;

    private async Task<List<Subject>> ReadSubjectsFromStorageAsync()
    {
        // REMOVED: await _storageLock.WaitAsync(); - Lock removed from read operation
        try
        {
            _logger.LogInformation("Attempting to read subjects from storage: {Path}", _subjectFileRelativePath);
            var stream = await _storageService.ReadFileAsStreamAsync(_subjectFileRelativePath);
            if (stream == null)
            {
                _logger.LogInformation("Subject file not found or empty at {Path}.", _subjectFileRelativePath);
                return new List<Subject>();
            }

            List<Subject> subjects;
            using (var reader = new StreamReader(stream))
            using (var csv = new CsvReader(reader, _csvConfig))
            {
                csv.Context.TypeConverterCache.AddConverter<SubjectLevel>(new EnumConverter(typeof(SubjectLevel)));
                csv.Context.RegisterClassMap<SubjectImportMap>(); // Use map for reading too
                // Manually iterate IAsyncEnumerable
                subjects = new List<Subject>();
                await foreach(var record in csv.GetRecordsAsync<Subject>())
                {
                    subjects.Add(record);
                }
            }
            _logger.LogInformation("Read {Count} subjects from {File}", subjects.Count, _subjectFileRelativePath);
            return subjects;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error reading subjects from storage: {Path}", _subjectFileRelativePath);
            return new List<Subject>();
        }
        // REMOVED: finally block with _storageLock.Release();
        // REMOVED: Redundant catch block
        // Lock is NOT released here as it wasn't acquired for pure read.
    }

    private async Task WriteSubjectsToStorageAsync(IEnumerable<Subject> subjects)
    {
        // Should be called within _storageLock
        try
        {
            using var memoryStream = new MemoryStream();
            using (var writer = new StreamWriter(memoryStream, leaveOpen: true))
            using (var csv = new CsvWriter(writer, _csvConfig))
            {
                csv.Context.TypeConverterCache.AddConverter<SubjectLevel>(new EnumConverter(typeof(SubjectLevel)));
                csv.Context.RegisterClassMap<SubjectImportMap>(); // Use map for writing order
                await csv.WriteRecordsAsync(subjects);
            }
            memoryStream.Position = 0;

            bool success = await _storageService.SaveFileAsync(_subjectFileRelativePath, memoryStream);
             if (success)
            {
                _logger.LogInformation("Wrote {Count} subjects to storage: {Path}", subjects.Count(), _subjectFileRelativePath);
            }
            else
            {
                 _logger.LogError("Failed to write subjects to storage: {Path}", _subjectFileRelativePath);
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error writing subjects to storage: {Path}", _subjectFileRelativePath);
            throw;
        }
    }

    public async Task<List<Subject>> GetAllSubjectsAsync()
    {
        return await ReadSubjectsFromStorageAsync();
    }

    public async Task<Subject?> GetSubjectByCodeAsync(string subjectCode)
    {
        var subjects = await ReadSubjectsFromStorageAsync();
        return subjects.FirstOrDefault(s => s.CxcSubjectCode == subjectCode);
    }

    public async Task<bool> AddSubjectAsync(Subject subject)
    {
        await _storageLock.WaitAsync();
        try
        {
            if (!Regex.IsMatch(subject.CxcSubjectCode, @"^\d{8}$")) // Using 8 digits now
            {
                 _logger.LogWarning("Attempted to add subject with invalid code format: {Code}", subject.CxcSubjectCode);
                 return false;
            }

            var subjects = await ReadSubjectsFromStorageAsync();
            if (subjects.Any(s => s.CxcSubjectCode == subject.CxcSubjectCode))
            {
                _logger.LogWarning("Attempted to add subject with duplicate code: {Code}", subject.CxcSubjectCode);
                return false;
            }

            subjects.Add(subject);
            await WriteSubjectsToStorageAsync(subjects);
            _logger.LogInformation("Added subject: {Code} - {Name}", subject.CxcSubjectCode, subject.Name);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed during AddSubject operation for {Code}", subject.CxcSubjectCode);
            return false;
        }
        finally
        {
            _storageLock.Release();
        }
    }

    public async Task<bool> UpdateSubjectAsync(Subject subject)
    {
        await _storageLock.WaitAsync();
        try
        {
             if (!Regex.IsMatch(subject.CxcSubjectCode, @"^\d{8}$")) // Using 8 digits now
            {
                 _logger.LogWarning("Attempted to update subject with invalid code format: {Code}", subject.CxcSubjectCode);
                 return false;
            }

            var subjects = await ReadSubjectsFromStorageAsync();
            var index = subjects.FindIndex(s => s.CxcSubjectCode == subject.CxcSubjectCode);

            if (index == -1)
            {
                _logger.LogWarning("Attempted to update non-existent subject: {Code}", subject.CxcSubjectCode);
                return false;
            }

            subjects[index] = subject;
            await WriteSubjectsToStorageAsync(subjects);
            _logger.LogInformation("Updated subject: {Code} - {Name}", subject.CxcSubjectCode, subject.Name);
            return true;
        }
         catch (Exception ex)
        {
            _logger.LogError(ex, "Failed during UpdateSubject operation for {Code}", subject.CxcSubjectCode);
            return false;
        }
        finally
        {
            _storageLock.Release();
        }
    }

    public async Task<bool> DeleteSubjectAsync(string subjectCode)
    {
        await _storageLock.WaitAsync();
        try
        {
            var subjects = await ReadSubjectsFromStorageAsync();
            var initialCount = subjects.Count;
            subjects.RemoveAll(s => s.CxcSubjectCode == subjectCode);

            if (subjects.Count == initialCount)
            {
                _logger.LogWarning("Attempted to delete non-existent subject: {Code}", subjectCode);
                return false;
            }

            await WriteSubjectsToStorageAsync(subjects);
            _logger.LogInformation("Deleted subject: {Code}", subjectCode);
            return true;
        }
         catch (Exception ex)
        {
            _logger.LogError(ex, "Failed during DeleteSubject operation for {Code}", subjectCode);
            return false;
        }
        finally
        {
            _storageLock.Release();
        }
    }

    public async Task<bool> DeleteAllSubjectsAsync()
    {
        await _storageLock.WaitAsync();
        try
        {
            await WriteSubjectsToStorageAsync(new List<Subject>());
            _logger.LogInformation("Deleted all subjects from {File}", _subjectFileRelativePath);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to delete all subjects from {File}", _subjectFileRelativePath);
            return false;
        }
        finally
        {
            _storageLock.Release();
        }
    }

    public async Task<int> ImportSubjectsFromCsvAsync(Stream stream)
    {
        await _storageLock.WaitAsync(); // Lock for replacing the file
        List<Subject> importedSubjects = new();
        try
        {
            using var reader = new StreamReader(stream);
            var importConfig = new CsvConfiguration(CultureInfo.InvariantCulture)
            {
                HasHeaderRecord = true,
                MissingFieldFound = null,
                HeaderValidated = null,
                PrepareHeaderForMatch = args => args.Header.ToLower().Replace(" ", "").Replace("_", ""),
            };
            using var csv = new CsvReader(reader, importConfig);

            csv.Context.RegisterClassMap<SubjectImportMap>();
            csv.Context.TypeConverterCache.AddConverter<SubjectLevel>(new EnumConverter(typeof(SubjectLevel)));

            await foreach (var record in csv.GetRecordsAsync<Subject>())
            {
                if (record != null && !string.IsNullOrWhiteSpace(record.CxcSubjectCode) && Regex.IsMatch(record.CxcSubjectCode, @"^\d{8}$") && !string.IsNullOrWhiteSpace(record.Name)) // Using 8 digits
                {
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

            await WriteSubjectsToStorageAsync(importedSubjects); // Write within lock
            _logger.LogInformation("Successfully imported {Count} subjects from stream.", importedSubjects.Count);
            return importedSubjects.Count;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error importing subjects from CSV stream.");
            return 0;
        }
         finally
        {
            _storageLock.Release();
        }
    }

     // --- Helper ClassMap for Import and Writing ---
    private sealed class SubjectImportMap : ClassMap<Subject>
    {
        public SubjectImportMap()
        {
            // Define mapping order
            Map(m => m.CxcSubjectCode).Index(0).Name("cxcsubjectcode", "subjectcode", "code");
            Map(m => m.Name).Index(1).Name("name", "subjectname");
            Map(m => m.Level).Index(2).Name("level").TypeConverter(new EnumConverter(typeof(SubjectLevel)));
        }
    }
}
