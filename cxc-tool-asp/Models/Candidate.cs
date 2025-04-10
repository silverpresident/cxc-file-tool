using System.ComponentModel.DataAnnotations;

namespace cxc_tool_asp.Models;

/// <summary>
/// Represents a candidate whose SBA projects are managed by the system.
/// </summary>
public record Candidate
{
    // Properties reordered to match desired CSV output: Class, Name, Exam, CxcRegistrationNo, Subjects

    /// <summary>
    /// The class or form the candidate belongs to (optional).
    /// </summary>
    [StringLength(50)]
    public string? Class { get; init; }

    /// <summary>
    /// The full name of the candidate.
    /// </summary>
    [Required]
    [StringLength(100)]
    public required string Name { get; init; }

    /// <summary>
    /// The exam the candidate is registered for (e.g., CSEC, CAPE) (optional).
    /// Added based on feedback.
    /// </summary>
    [StringLength(50)]
    public string? Exam { get; init; }

    /// <summary>
    /// The candidate's unique 10-digit CXC registration number.
    /// This is the primary identifier.
    /// </summary>
    [Required]
    [RegularExpression(@"^\d{10}$", ErrorMessage = "Registration number must be 10 digits.")]
    public required string CxcRegistrationNo { get; init; }

    /// <summary>
    /// A comma-separated list of subjects the candidate is registered for (optional).
    /// Might be better represented as a list if complex querying is needed,
    /// but keeping as string for simple CSV storage.
    /// </summary>
    public string? Subjects { get; init; }

    /// <summary>
    /// Extracts the last 4 digits of the CXC registration number, used for file naming.
    /// </summary>
    public string CandidateCode => CxcRegistrationNo?.Length == 10 ? CxcRegistrationNo.Substring(6) : string.Empty;
}
