using System.ComponentModel.DataAnnotations;

namespace cxc_tool_asp.Models;

/// <summary>
/// Represents an examination subject offered by CXC.
/// </summary>
public record Subject
{
    /// <summary>
    /// The full name of the subject.
    /// </summary>
    [Required]
    [StringLength(100)]
    public required string Name { get; init; }

    /// <summary>
    /// The unique CXC subject code (e.g., 100123).
    /// This is the primary identifier.
    /// </summary>
    [Required]
    [RegularExpression(@"^\d{8}$", ErrorMessage = "Subject code must be 8 digits.")] // Changed from 6 to 8 digits
    public required string CxcSubjectCode { get; init; }

    /// <summary>
    /// The level of the subject (e.g., CSEC, CAPE).
    /// </summary>
    [Required]
    public required SubjectLevel Level { get; init; }
}

/// <summary>
/// Defines the possible levels for a CXC subject.
/// </summary>
public enum SubjectLevel
{
    CSEC,
    CAPE
}
