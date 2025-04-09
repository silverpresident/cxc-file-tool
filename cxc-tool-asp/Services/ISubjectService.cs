using cxc_tool_asp.Models;

namespace cxc_tool_asp.Services;

/// <summary>
/// Interface for managing subject data.
/// </summary>
public interface ISubjectService
{
    /// <summary>
    /// Retrieves all subjects.
    /// </summary>
    /// <returns>A list of all subjects.</returns>
    Task<List<Subject>> GetAllSubjectsAsync();

    /// <summary>
    /// Retrieves a subject by its CXC subject code.
    /// </summary>
    /// <param name="subjectCode">The 6-digit subject code.</param>
    /// <returns>The subject object if found; otherwise, null.</returns>
    Task<Subject?> GetSubjectByCodeAsync(string subjectCode);

    /// <summary>
    /// Adds a new subject to the list.
    /// </summary>
    /// <param name="subject">The subject object to add.</param>
    /// <returns>True if addition was successful; otherwise, false (e.g., duplicate subject code).</returns>
    Task<bool> AddSubjectAsync(Subject subject);

    /// <summary>
    /// Updates an existing subject.
    /// </summary>
    /// <param name="subject">The subject object with updated details.</param>
    /// <returns>True if the update was successful; otherwise, false.</returns>
    Task<bool> UpdateSubjectAsync(Subject subject);

    /// <summary>
    /// Deletes a subject by its subject code.
    /// </summary>
    /// <param name="subjectCode">The subject code of the subject to delete.</param>
    /// <returns>True if the deletion was successful; otherwise, false.</returns>
    Task<bool> DeleteSubjectAsync(string subjectCode);

    /// <summary>
    /// Deletes all subjects.
    /// </summary>
    /// <returns>True if deletion was successful; otherwise, false.</returns>
    Task<bool> DeleteAllSubjectsAsync();

    /// <summary>
    /// Imports subjects from a provided CSV stream, replacing the current list.
    /// Extracts relevant fields based on expected headers.
    /// </summary>
    /// <param name="stream">The stream containing the CSV data to import.</param>
    /// <returns>The number of subjects successfully imported.</returns>
    Task<int> ImportSubjectsFromCsvAsync(Stream stream);

    /// <summary>
    /// Gets the file path for the subjects CSV file.
    /// </summary>
    /// <returns>The full path to the subjects CSV file.</returns>
    string GetSubjectFilePath();
}
