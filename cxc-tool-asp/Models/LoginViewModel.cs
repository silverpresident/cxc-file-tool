using System.ComponentModel.DataAnnotations;

namespace cxc_tool_asp.Models;

/// <summary>
/// View model for the user login page.
/// </summary>
public class LoginViewModel
{
    /// <summary>
    /// The user's display name entered during login.
    /// </summary>
    [Required(ErrorMessage = "Display Name is required.")]
    [Display(Name = "Display Name")]
    public required string DisplayName { get; set; }

    /// <summary>
    /// The user's password entered during login.
    /// </summary>
    [Required(ErrorMessage = "Password is required.")]
    [DataType(DataType.Password)]
    public required string Password { get; set; }

    /// <summary>
    /// Optional field to remember the user's login session.
    /// Not implemented in the initial simple auth, but good practice to include.
    /// </summary>
    [Display(Name = "Remember me?")]
    public bool RememberMe { get; set; }
}
