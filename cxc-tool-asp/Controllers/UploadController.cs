using cxc_tool_asp.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims; // Required for User.FindFirstValue

namespace cxc_tool_asp.Controllers;

[Authorize] // Requires login
public class UploadController : Controller
{
    private readonly IFileService _fileService;
    private readonly ILogger<UploadController> _logger;

    // Configuration for file validation
    private const long MaxFileSize = 10 * 1024 * 1024; // 10 MB
    private static readonly string[] AllowedExtensions = { ".pdf", ".docx", ".jpg", ".jpeg", ".png" };


    public UploadController(IFileService fileService, ILogger<UploadController> logger)
    {
        _fileService = fileService;
        _logger = logger;
    }

    // GET: /Upload/Index
    public async Task<IActionResult> Index()
    {
        var userFolderName = User.FindFirstValue("FolderName");
        if (string.IsNullOrEmpty(userFolderName))
        {
            _logger.LogWarning("User '{UserName}' attempted to access upload page without FolderName claim.", User.Identity?.Name);
            // Handle error - maybe redirect to logout or an error page
            return RedirectToAction("Login", "Account"); // Redirect to login if claim is missing
        }

        // Get list of files currently in the user's folder
        var files = await _fileService.GetFilesAsync(userFolderName);

        // TODO: Pass the file list and potentially other needed data to the view
        ViewBag.UserFiles = files;
        _logger.LogInformation("User '{UserName}' accessed Upload page. Found {FileCount} files.", User.Identity?.Name, files.Count);

        return View(); // Placeholder - View needs to be created
    }

    // POST: /Upload/Index (or a different action name like UploadFile)
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> UploadFile(IFormFile file) // Assuming single file upload for now
    {
         var userFolderName = User.FindFirstValue("FolderName");
        if (string.IsNullOrEmpty(userFolderName))
        {
            _logger.LogWarning("User '{UserName}' attempted to upload file without FolderName claim.", User.Identity?.Name);
            return Unauthorized("User folder information not found."); // Or redirect
        }

        if (file == null || file.Length == 0)
        {
            TempData["UploadError"] = "Please select a file to upload.";
            return RedirectToAction(nameof(Index));
        }

        // --- File Validation ---
        var fileExtension = Path.GetExtension(file.FileName).ToLowerInvariant();
        if (string.IsNullOrEmpty(fileExtension) || !AllowedExtensions.Contains(fileExtension))
        {
            TempData["UploadError"] = $"Invalid file type. Allowed types are: {string.Join(", ", AllowedExtensions)}";
            _logger.LogWarning("User '{UserName}' attempted to upload invalid file type: {FileName}", User.Identity?.Name, file.FileName);
            return RedirectToAction(nameof(Index));
        }

        if (file.Length > MaxFileSize)
        {
            TempData["UploadError"] = $"File size exceeds the limit of {MaxFileSize / 1024 / 1024} MB.";
             _logger.LogWarning("User '{UserName}' attempted to upload oversized file: {FileName} ({FileSize} bytes)", User.Identity?.Name, file.FileName, file.Length);
            return RedirectToAction(nameof(Index));
        }
        // --- End File Validation ---


        var savedFileName = await _fileService.SaveFileAsync(userFolderName, file);

        if (savedFileName != null)
        {
            TempData["UploadSuccess"] = $"File '{savedFileName}' uploaded successfully.";
             _logger.LogInformation("User '{UserName}' successfully uploaded file '{FileName}'.", User.Identity?.Name, savedFileName);
        }
        else
        {
            TempData["UploadError"] = "An error occurred while uploading the file.";
             _logger.LogError("User '{UserName}' failed to upload file '{OriginalFileName}'.", User.Identity?.Name, file.FileName);
        }

        return RedirectToAction(nameof(Index));
    }

    // TODO: Add action for deleting an uploaded file (before renaming)
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteUploadedFile(string fileName)
    {
        var userFolderName = User.FindFirstValue("FolderName");
        if (string.IsNullOrEmpty(userFolderName))
        {
             return Unauthorized("User folder information not found.");
        }
         if (string.IsNullOrEmpty(fileName))
        {
            TempData["UploadError"] = "Invalid file name provided for deletion.";
            return RedirectToAction(nameof(Index));
        }

        var success = await _fileService.DeleteFileAsync(userFolderName, fileName);

        if(success)
        {
            TempData["UploadSuccess"] = $"File '{fileName}' deleted successfully.";
             _logger.LogInformation("User '{UserName}' deleted uploaded file '{FileName}'.", User.Identity?.Name, fileName);
        }
        else
        {
            TempData["UploadError"] = $"An error occurred while deleting file '{fileName}'.";
             _logger.LogError("User '{UserName}' failed to delete uploaded file '{FileName}'.", User.Identity?.Name, fileName);
        }
         return RedirectToAction(nameof(Index));
    }

}
