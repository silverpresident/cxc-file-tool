using cxc_tool_asp.Models;
using CsvHelper;
using CsvHelper.Configuration;
using System.Globalization;
using System.Text.RegularExpressions;

namespace cxc_tool_asp.Services;

/// <summary>
/// Service implementation for managing candidate data stored in a year-specific CSV file.
/// </summary>
public class CandidateService : ICandidateService
{
    private readonly string _dataFolderPath;
    private readonly string _candidateFilePath;
    private static readonly SemaphoreSlim _fileLock = new(1, 1); // Lock for file access
    private readonly ILogger<CandidateService> _logger;

    // Basic configuration for CsvHelper
    private readonly CsvConfiguration _csvConfig = new(CultureInfo.InvariantCulture)
    {
        HasHeaderRecord = true,
        MissingFieldFound = null, // Ignore missing fields
        HeaderValidated = null, // Ignore header validation
    };

    public CandidateService(IWebHostEnvironment env, ILogger<CandidateService> logger)
    {
        _dataFolderPath = Path.Combine(env.ContentRootPath, "Data");
        Directory.CreateDirectory(_dataFolderPath); // Ensure directory exists
        _candidateFilePath = Path.Combine(_dataFolderPath, $"{DateTime.Now.Year}cand.csv");
        _logger = logger;
    }

    public string GetCandidateFilePath() => _candidateFilePath;

    private async Task<List<Candidate>> ReadCandidatesFromFileAsync()
    {
        await _fileLock.WaitAsync();
        try
        {
            if (!File.Exists(_candidateFilePath))
            {
                return new List<Candidate>(); // Return empty list if file doesn't exist
            }

            using var reader = new StreamReader(_candidateFilePath);
            using var csv = new CsvReader(reader, _csvConfig);
            // Need to handle potential mapping issues if CSV headers don't match model exactly
            // For now, assume direct mapping works or use a ClassMap if needed.
            var candidates = csv.GetRecords<Candidate>().ToList();
            _logger.LogInformation("Read {Count} candidates from {File}", candidates.Count, _candidateFilePath);
            return candidates;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error reading candidates from CSV file: {FilePath}", _candidateFilePath);
            return new List<Candidate>(); // Return empty list on error
        }
        finally
        {
            _fileLock.Release();
        }
    }

    private async Task WriteCandidatesToFileAsync(IEnumerable<Candidate> candidates)
    {
        await _fileLock.WaitAsync();
        try
        {
            using var writer = new StreamWriter(_candidateFilePath); // Overwrites existing file
            using var csv = new CsvWriter(writer, _csvConfig);
            await csv.WriteRecordsAsync(candidates);
            _logger.LogInformation("Wrote {Count} candidates to {File}", candidates.Count(), _candidateFilePath);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error writing candidates to CSV file: {FilePath}", _candidateFilePath);
            throw; // Re-throw to indicate failure
        }
        finally
        {
            _fileLock.Release();
        }
    }

    public async Task<List<Candidate>> GetAllCandidatesAsync()
    {
        return await ReadCandidatesFromFileAsync();
    }

    public async Task<Candidate?> GetCandidateByRegistrationNoAsync(string registrationNo)
    {
        var candidates = await ReadCandidatesFromFileAsync();
        return candidates.FirstOrDefault(c => c.CxcRegistrationNo == registrationNo);
    }

    public async Task<bool> AddCandidateAsync(Candidate candidate)
    {
        var candidates = await ReadCandidatesFromFileAsync();

        // Validate registration number format (redundant if model validation works, but good defense)
        if (!Regex.IsMatch(candidate.CxcRegistrationNo, @"^\d{10}$"))
        {
             _logger.LogWarning("Attempted to add candidate with invalid registration number format: {RegNo}", candidate.CxcRegistrationNo);
             return false;
        }

        // Check for duplicates
        if (candidates.Any(c => c.CxcRegistrationNo == candidate.CxcRegistrationNo))
        {
            _logger.LogWarning("Attempted to add candidate with duplicate registration number: {RegNo}", candidate.CxcRegistrationNo);
            return false;
        }

        candidates.Add(candidate);

        try
        {
            await WriteCandidatesToFileAsync(candidates);
            _logger.LogInformation("Added candidate: {RegNo}", candidate.CxcRegistrationNo);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to write file after adding candidate: {RegNo}", candidate.CxcRegistrationNo);
            return false;
        }
    }

    public async Task<bool> UpdateCandidateAsync(Candidate candidate)
    {
         // Validate registration number format
        if (!Regex.IsMatch(candidate.CxcRegistrationNo, @"^\d{10}$"))
        {
             _logger.LogWarning("Attempted to update candidate with invalid registration number format: {RegNo}", candidate.CxcRegistrationNo);
             return false;
        }

        var candidates = await ReadCandidatesFromFileAsync();
        var index = candidates.FindIndex(c => c.CxcRegistrationNo == candidate.CxcRegistrationNo);

        if (index == -1)
        {
            _logger.LogWarning("Attempted to update non-existent candidate: {RegNo}", candidate.CxcRegistrationNo);
            return false; // Candidate not found
        }

        candidates[index] = candidate; // Replace existing record

        try
        {
            await WriteCandidatesToFileAsync(candidates);
            _logger.LogInformation("Updated candidate: {RegNo}", candidate.CxcRegistrationNo);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to write file after updating candidate: {RegNo}", candidate.CxcRegistrationNo);
            return false;
        }
    }

    public async Task<bool> DeleteCandidateAsync(string registrationNo)
    {
        var candidates = await ReadCandidatesFromFileAsync();
        var initialCount = candidates.Count;
        candidates.RemoveAll(c => c.CxcRegistrationNo == registrationNo);

        if (candidates.Count == initialCount)
        {
             _logger.LogWarning("Attempted to delete non-existent candidate: {RegNo}", registrationNo);
            return false; // Candidate not found
        }

        try
        {
            await WriteCandidatesToFileAsync(candidates);
            _logger.LogInformation("Deleted candidate: {RegNo}", registrationNo);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to write file after deleting candidate: {RegNo}", registrationNo);
            return false;
        }
    }

    public async Task<bool> DeleteAllCandidatesAsync()
    {
        try
        {
            // Write an empty list to the file
            await WriteCandidatesToFileAsync(new List<Candidate>());
            _logger.LogInformation("Deleted all candidates from {File}", _candidateFilePath);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to delete all candidates from {File}", _candidateFilePath);
            return false;
        }
    }

    public async Task<int> ImportCandidatesFromCsvAsync(Stream stream)
    {
        List<Candidate> importedCandidates = new();
        try
        {
            using var reader = new StreamReader(stream);
            // Use a more flexible config for import, allowing different header names potentially
            var importConfig = new CsvConfiguration(CultureInfo.InvariantCulture)
            {
                HasHeaderRecord = true,
                MissingFieldFound = null,
                HeaderValidated = null,
                PrepareHeaderForMatch = args => args.Header.ToLower().Replace(" ", "").Replace("_", ""), // Normalize header
            };
            using var csv = new CsvReader(reader, importConfig);

            // Define a ClassMap for flexible mapping during import
            csv.Context.RegisterClassMap<CandidateImportMap>();

            await foreach (var record in csv.GetRecordsAsync<Candidate>())
            {
                // Basic validation - ensure RegNo is 10 digits
                 if (record != null && !string.IsNullOrWhiteSpace(record.CxcRegistrationNo) && Regex.IsMatch(record.CxcRegistrationNo, @"^\d{10}$"))
                 {
                     // Avoid duplicates within the import file itself
                     if (!importedCandidates.Any(c => c.CxcRegistrationNo == record.CxcRegistrationNo))
                     {
                        importedCandidates.Add(record);
                     }
                     else
                     {
                         _logger.LogWarning("Duplicate registration number found during import, skipping: {RegNo}", record.CxcRegistrationNo);
                     }
                 }
                 else
                 {
                      _logger.LogWarning("Invalid or missing registration number found during import, skipping record near line {Row}", csv.Context.Parser.Row);
                 }
            }

            // Replace existing file content with imported data
            await WriteCandidatesToFileAsync(importedCandidates);
            _logger.LogInformation("Successfully imported {Count} candidates from stream.", importedCandidates.Count);
            return importedCandidates.Count;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error importing candidates from CSV stream.");
            return 0; // Indicate failure
        }
    }

    // --- Helper ClassMap for Import ---
    private sealed class CandidateImportMap : ClassMap<Candidate>
    {
        public CandidateImportMap()
        {
            // Define mapping in the desired CSV column order
            // Use index for explicit order and Name for header variations (case-insensitive due to PrepareHeaderForMatch)

            // Index 0: Class
            Map(m => m.Class).Index(0).Name("class", "form").Optional();

            // Index 1: Name
            Map(m => m.Name).Index(1).Name("name", "candidatename", "fullname", "partyname"); // Added partyname

            // Index 2: Exam (New optional field)
            Map(m => m.Exam).Index(2).Name("exam", "examination").Optional();

            // Index 3: CxcRegistrationNo
            Map(m => m.CxcRegistrationNo).Index(3).Name("cxcregistrationno", "registrationno", "regno", "candidateid", "current_cxc_candidate_no", "cxc_candidate_no"); // Added alternatives

            // Index 4: Subjects
            Map(m => m.Subjects).Index(4).Name("subjects", "subjectlist").Optional();

        }
    }
}
