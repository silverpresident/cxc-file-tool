using cxc_tool_asp.Models;
using cxc_tool_asp.Services;
using Microsoft.AspNetCore.Authentication;
using Microsoft.AspNetCore.Authentication.Cookies;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims;

namespace cxc_tool_asp.Controllers;

public class AccountController : Controller
{
    private readonly IUserService _userService;
    private readonly ILogger<AccountController> _logger;

    public AccountController(IUserService userService, ILogger<AccountController> logger)
    {
        _userService = userService;
        _logger = logger;
    }

    // GET: /Account/Login
    [AllowAnonymous] // Allow access to login page without being authenticated
    public IActionResult Login(string? returnUrl = null)
    {
        ViewData["ReturnUrl"] = returnUrl;
        // If user is already authenticated, redirect them away from login page
        if (User.Identity?.IsAuthenticated ?? false)
        {
            return RedirectToLocal(returnUrl);
        }
        return View();
    }

    // POST: /Account/Login
    [HttpPost]
    [AllowAnonymous]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> Login(LoginViewModel model, string? returnUrl = null)
    {
        ViewData["ReturnUrl"] = returnUrl;
        if (ModelState.IsValid)
        {
            var user = await _userService.ValidateCredentialsAsync(model.DisplayName, model.Password);
            if (user != null)
            {
                _logger.LogInformation("User '{DisplayName}' logged in successfully.", user.DisplayName);

                var claims = new List<Claim>
                {
                    new Claim(ClaimTypes.NameIdentifier, user.Id.ToString()),
                    new Claim(ClaimTypes.Name, user.DisplayName),
                    // Add role claim for authorization
                    new Claim(ClaimTypes.Role, user.IsAdmin ? "Admin" : "User"),
                    // Add folder name claim for easy access later
                    new Claim("FolderName", user.FolderName)
                };

                var claimsIdentity = new ClaimsIdentity(
                    claims, CookieAuthenticationDefaults.AuthenticationScheme);

                var authProperties = new AuthenticationProperties
                {
                    //AllowRefresh = <bool>, // Refreshing the authentication session should be allowed.
                    //ExpiresUtc = DateTimeOffset.UtcNow.AddMinutes(10), // The time at which the authentication ticket expires.
                    IsPersistent = model.RememberMe, // Persist cookie across browser sessions if RememberMe is checked
                    //IssuedUtc = <DateTimeOffset>, // The time at which the authentication ticket was issued.
                    //RedirectUri = <string> // The full path or absolute URI to be used as an http redirect response value.
                };

                await HttpContext.SignInAsync(
                    CookieAuthenticationDefaults.AuthenticationScheme,
                    new ClaimsPrincipal(claimsIdentity),
                    authProperties);

                return RedirectToLocal(returnUrl);
            }
            else
            {
                _logger.LogWarning("Invalid login attempt for user '{DisplayName}'.", model.DisplayName);
                ModelState.AddModelError(string.Empty, "Invalid login attempt.");
                return View(model);
            }
        }

        // If we got this far, something failed, redisplay form
        return View(model);
    }

    // POST: /Account/Logout
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> Logout()
    {
        _logger.LogInformation("User '{Name}' logged out.", User.Identity?.Name ?? "Unknown");
        await HttpContext.SignOutAsync(CookieAuthenticationDefaults.AuthenticationScheme);
        return RedirectToAction(nameof(HomeController.Index), "Home"); // Redirect to home page after logout
    }

    // GET: /Account/AccessDenied
    [AllowAnonymous]
    public IActionResult AccessDenied()
    {
        return View();
    }


    // Helper method to prevent open redirect attacks
    private IActionResult RedirectToLocal(string? returnUrl)
    {
        if (Url.IsLocalUrl(returnUrl))
        {
            return Redirect(returnUrl);
        }
        else
        {
            // Default redirect if returnUrl is not local (e.g., to dashboard or home)
            return RedirectToAction(nameof(HomeController.Index), "Home");
        }
    }
}
