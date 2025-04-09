using cxc_tool_asp.Models;

namespace cxc_tool_asp.Services;

/// <summary>
/// Interface for managing candidate data.
/// </summary>
public interface ICandidateService
{
    /// <summary>
    /// Retrieves all candidates for the current year.
    /// </summary>
    /// <returns>A list of all candidates for the current year.</returns>
    Task<List<Candidate>> GetAllCandidatesAsync();

    /// <summary>
    /// Retrieves a candidate by their CXC registration number for the current year.
    /// </summary>
    /// <param name="registrationNo">The 10-digit registration number.</param>
    /// <returns>The candidate object if found; otherwise, null.</returns>
    Task<Candidate?> GetCandidateByRegistrationNoAsync(string registrationNo);

    /// <summary>
    /// Adds a new candidate to the current year's list.
    /// </summary>
    /// <param name="candidate">The candidate object to add.</param>
    /// <returns>True if addition was successful; otherwise, false (e.g., duplicate registration number).</returns>
    Task<bool> AddCandidateAsync(Candidate candidate);

    /// <summary>
    /// Updates an existing candidate in the current year's list.
    /// </summary>
    /// <param name="candidate">The candidate object with updated details.</param>
    /// <returns>True if the update was successful; otherwise, false.</returns>
    Task<bool> UpdateCandidateAsync(Candidate candidate);

    /// <summary>
    /// Deletes a candidate from the current year's list by registration number.
    /// </summary>
    /// <param name="registrationNo">The registration number of the candidate to delete.</param>
    /// <returns>True if the deletion was successful; otherwise, false.</returns>
    Task<bool> DeleteCandidateAsync(string registrationNo);

    /// <summary>
    /// Deletes all candidates for the current year.
    /// </summary>
    /// <returns>True if deletion was successful; otherwise, false.</returns>
    Task<bool> DeleteAllCandidatesAsync();

    /// <summary>
    /// Imports candidates from a provided CSV stream, replacing the current year's list.
    /// Extracts relevant fields based on expected headers.
    /// </summary>
    /// <param name="stream">The stream containing the CSV data to import.</param>
    /// <returns>The number of candidates successfully imported.</returns>
    Task<int> ImportCandidatesFromCsvAsync(Stream stream);

    /// <summary>
    /// Gets the file path for the current year's candidate CSV file.
    /// </summary>
    /// <returns>The full path to the candidate CSV file.</returns>
    string GetCandidateFilePath();
}
