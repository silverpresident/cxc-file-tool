using cxc_tool_asp.Models;
using CsvHelper;
using CsvHelper.Configuration;
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
/// Service implementation for managing candidate data using IStorageService.
/// </summary>
public class CandidateService : ICandidateService
{
    private readonly IStorageService _storageService;
    private readonly string _candidateFileRelativePath; // e.g., Data/2025cand.csv
    private static readonly SemaphoreSlim _storageLock = new(1, 1); // Lock for storage access (simplistic)
    private readonly ILogger<CandidateService> _logger;

    private readonly CsvConfiguration _csvConfig = new(CultureInfo.InvariantCulture)
    {
        HasHeaderRecord = true,
        MissingFieldFound = null,
        HeaderValidated = null,
        PrepareHeaderForMatch = args => args.Header.ToLower().Replace(" ", "").Replace("_", "")
    };

    public CandidateService(IStorageService storageService, ILogger<CandidateService> logger)
    {
        _storageService = storageService;
        _logger = logger;
        string DataPrefix = _storageService.GetDataFolderName();
        _candidateFileRelativePath = $"{DataPrefix}/{DateTime.Now.Year}cand.csv";
    }

    // Keep this method, but it now returns the relative path
    public string GetCandidateFilePath() => _candidateFileRelativePath;

    private async Task<List<Candidate>> ReadCandidatesFromStorageAsync()
    {
        // REMOVED: await _storageLock.WaitAsync(); - Lock removed from read operation
        try
        {
            _logger.LogInformation("Attempting to read candidates from storage: {Path}", _candidateFileRelativePath);
            var stream = await _storageService.ReadFileAsStreamAsync(_candidateFileRelativePath);
            if (stream == null)
            {
                _logger.LogInformation("Candidate file not found or empty at {Path}.", _candidateFileRelativePath);
                return new List<Candidate>();
            }

            List<Candidate> candidates;
            using (var reader = new StreamReader(stream)) // Dispose stream after reading
            using (var csv = new CsvReader(reader, _csvConfig))
            {
                // Use the import map for consistency, even when reading the stored file
                csv.Context.RegisterClassMap<CandidateImportMap>();
                // Manually iterate IAsyncEnumerable
                candidates = new List<Candidate>();
                await foreach (var record in csv.GetRecordsAsync<Candidate>())
                {
                    candidates.Add(record);
                }
            }
            _logger.LogInformation("Read {Count} candidates from {File}", candidates.Count, _candidateFileRelativePath);
            return candidates;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error reading candidates from storage: {Path}", _candidateFileRelativePath);
            return new List<Candidate>();
        }
        // REMOVED: finally block with _storageLock.Release();
        // REMOVED: Redundant catch block
        // Lock is NOT released here as it wasn't acquired for pure read.
    }

    private async Task WriteCandidatesToStorageAsync(IEnumerable<Candidate> candidates)
    {
        // This method writes the entire list back to storage.
        // It should ideally be called within the _storageLock.
        try
        {
            using var memoryStream = new MemoryStream();
            using (var writer = new StreamWriter(memoryStream, leaveOpen: true))
            using (var csv = new CsvWriter(writer, _csvConfig))
            {
                 // Use the import map to control writing order
                csv.Context.RegisterClassMap<CandidateImportMap>();
                await csv.WriteRecordsAsync(candidates);
            }
            memoryStream.Position = 0;

            bool success = await _storageService.SaveFileAsync(_candidateFileRelativePath, memoryStream);
            if (success)
            {
                _logger.LogInformation("Wrote {Count} candidates to storage: {Path}", candidates.Count(), _candidateFileRelativePath);
            }
            else
            {
                 _logger.LogError("Failed to write candidates to storage: {Path}", _candidateFileRelativePath);
                 // Consider how to handle this failure
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error writing candidates to storage: {Path}", _candidateFileRelativePath);
            throw;
        }
    }

    public async Task<List<Candidate>> GetAllCandidatesAsync()
    {
        // No caching implemented here for simplicity, always reads from storage
        return await ReadCandidatesFromStorageAsync();
    }

    public async Task<Candidate?> GetCandidateByRegistrationNoAsync(string registrationNo)
    {
        var candidates = await ReadCandidatesFromStorageAsync();
        return candidates.FirstOrDefault(c => c.CxcRegistrationNo == registrationNo);
    }

    public async Task<bool> AddCandidateAsync(Candidate candidate)
    {
        await _storageLock.WaitAsync();
        try
        {
            var candidates = await ReadCandidatesFromStorageAsync(); // Read within lock

            if (!Regex.IsMatch(candidate.CxcRegistrationNo, @"^\d{10}$"))
            {
                 _logger.LogWarning("Attempted to add candidate with invalid registration number format: {RegNo}", candidate.CxcRegistrationNo);
                 return false;
            }
            if (candidates.Any(c => c.CxcRegistrationNo == candidate.CxcRegistrationNo))
            {
                _logger.LogWarning("Attempted to add candidate with duplicate registration number: {RegNo}", candidate.CxcRegistrationNo);
                return false;
            }

            candidates.Add(candidate);
            await WriteCandidatesToStorageAsync(candidates); // Write within lock
            _logger.LogInformation("Added candidate: {RegNo}", candidate.CxcRegistrationNo);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed during AddCandidate operation for {RegNo}", candidate.CxcRegistrationNo);
            return false;
        }
        finally
        {
            _storageLock.Release();
        }
    }

    public async Task<bool> UpdateCandidateAsync(Candidate candidate)
    {
         await _storageLock.WaitAsync();
         try
         {
            if (!Regex.IsMatch(candidate.CxcRegistrationNo, @"^\d{10}$"))
            {
                 _logger.LogWarning("Attempted to update candidate with invalid registration number format: {RegNo}", candidate.CxcRegistrationNo);
                 return false;
            }

            var candidates = await ReadCandidatesFromStorageAsync();
            var index = candidates.FindIndex(c => c.CxcRegistrationNo == candidate.CxcRegistrationNo);

            if (index == -1)
            {
                _logger.LogWarning("Attempted to update non-existent candidate: {RegNo}", candidate.CxcRegistrationNo);
                return false;
            }

            candidates[index] = candidate;
            await WriteCandidatesToStorageAsync(candidates);
            _logger.LogInformation("Updated candidate: {RegNo}", candidate.CxcRegistrationNo);
            return true;
         }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed during UpdateCandidate operation for {RegNo}", candidate.CxcRegistrationNo);
            return false;
        }
        finally
        {
            _storageLock.Release();
        }
    }

    public async Task<bool> DeleteCandidateAsync(string registrationNo)
    {
        await _storageLock.WaitAsync();
        try
        {
            var candidates = await ReadCandidatesFromStorageAsync();
            var initialCount = candidates.Count;
            candidates.RemoveAll(c => c.CxcRegistrationNo == registrationNo);

            if (candidates.Count == initialCount)
            {
                 _logger.LogWarning("Attempted to delete non-existent candidate: {RegNo}", registrationNo);
                return false; // Not found, but maybe treat as success? Returning false for clarity.
            }

            await WriteCandidatesToStorageAsync(candidates);
            _logger.LogInformation("Deleted candidate: {RegNo}", registrationNo);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed during DeleteCandidate operation for {RegNo}", registrationNo);
            return false;
        }
        finally
        {
            _storageLock.Release();
        }
    }

    public async Task<bool> DeleteAllCandidatesAsync()
    {
        await _storageLock.WaitAsync();
        try
        {
            await WriteCandidatesToStorageAsync(new List<Candidate>()); // Write empty list
            _logger.LogInformation("Deleted all candidates from {File}", _candidateFileRelativePath);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to delete all candidates from {File}", _candidateFileRelativePath);
            return false;
        }
        finally
        {
            _storageLock.Release();
        }
    }

    public async Task<int> ImportCandidatesFromCsvAsync(Stream stream)
    {
        // This operation replaces the entire file, so needs the lock
        await _storageLock.WaitAsync();
        List<Candidate> importedCandidates = new();
        try
        {
            using var reader = new StreamReader(stream); // Don't dispose input stream
            var importConfig = new CsvConfiguration(CultureInfo.InvariantCulture)
            {
                HasHeaderRecord = true,
                MissingFieldFound = null,
                HeaderValidated = null,
                PrepareHeaderForMatch = args => args.Header.ToLower().Replace(" ", "").Replace("_", ""),
            };
            using var csv = new CsvReader(reader, importConfig);

            csv.Context.RegisterClassMap<CandidateImportMap>();

            await foreach (var record in csv.GetRecordsAsync<Candidate>())
            {
                 if (record != null && !string.IsNullOrWhiteSpace(record.CxcRegistrationNo) && Regex.IsMatch(record.CxcRegistrationNo, @"^\d{10}$"))
                 {
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

            // Replace existing file content
            await WriteCandidatesToStorageAsync(importedCandidates); // Write within lock
            _logger.LogInformation("Successfully imported {Count} candidates from stream.", importedCandidates.Count);
            return importedCandidates.Count;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error importing candidates from CSV stream.");
            return 0;
        }
         finally
        {
            _storageLock.Release();
        }
    }

    // --- Helper ClassMap for Import and Writing ---
    private sealed class CandidateImportMap : ClassMap<Candidate>
    {
        public CandidateImportMap()
        {
            // Define mapping in the desired CSV column order
            Map(m => m.Class).Index(0).Name("class", "form").Optional();
            Map(m => m.Name).Index(1).Name("name", "candidatename", "fullname", "partyname");
            Map(m => m.Exam).Index(2).Name("exam", "examination").Optional();
            Map(m => m.CxcRegistrationNo).Index(3).Name("cxcregistrationno", "registrationno", "regno", "candidateid", "current_cxc_candidate_no", "cxc_candidate_no");
            Map(m => m.Subjects).Index(4).Name("subjects", "subjectlist").Optional();
            // Ignore CandidateCode when writing/reading
            Map(m => m.CandidateCode).Ignore();
        }
    }
}
