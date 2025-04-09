using System.Diagnostics;
using Microsoft.AspNetCore.Mvc;
using cxc_tool_asp.Models;
using Microsoft.AspNetCore.Authorization; // Add using for Authorize attribute

namespace cxc_tool_asp.Controllers;

[Authorize] // Require login for all actions in this controller
public class HomeController : Controller
{
    private readonly ILogger<HomeController> _logger;

    public HomeController(ILogger<HomeController> logger)
    {
        _logger = logger;
    }

    public IActionResult Index()
    {
        return View();
    }

    // Removed Privacy action

    [ResponseCache(Duration = 0, Location = ResponseCacheLocation.None, NoStore = true)]
    [AllowAnonymous] // Allow access to error page without login
    public IActionResult Error()
    {
        return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
    }
}
