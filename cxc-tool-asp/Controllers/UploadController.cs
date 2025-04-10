using cxc_tool_asp.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims; // Required for User.FindFirstValue
using System.Collections.Generic;
using System.Threading.Tasks;
using Microsoft.AspNetCore.Http;
using System.IO;
using System.Linq;
using Microsoft.Extensions.Logging; // Added
using System; // Added

namespace cxc_tool_asp.Controllers;

[Authorize] // Requires login
public class UploadController : Controller
{
    // Inject IStorageService instead of IFileService
    private readonly IStorageService _storageService;
    private readonly ILogger<UploadController> _logger;
    private readonly string _userDataRelativePath = "Data"; // Base relative path for user folders

    // Configuration for file validation
    private const long MaxFileSize = 10 * 1024 * 1024; // 10 MB
    private static readonly string[] AllowedExtensions = { ".pdf", ".docx", ".jpg", ".jpeg", ".png" };


    public UploadController(IStorageService storageService, ILogger<UploadController> logger) // Updated constructor
    {
        _storageService = storageService; // Use injected IStorageService
        _logger = logger;
    }

    // Helper to construct the relative path for a user's file
    private string GetUserFileRelativePath(string userFolderName, string fileName)
    {
        var sanitizedFolderName = Path.GetFileName(userFolderName);
        var sanitizedFileName = Path.GetFileName(fileName);
        if (string.IsNullOrWhiteSpace(sanitizedFolderName) || sanitizedFolderName.Contains("..") ||
            string.IsNullOrWhiteSpace(sanitizedFileName) || sanitizedFileName.Contains(".."))
        {
            throw new ArgumentException("Invalid user folder name or file name.");
        }
        return Path.Combine(_userDataRelativePath, sanitizedFolderName, sanitizedFileName).Replace(Path.DirectorySeparatorChar, '/');
    }

     // Helper to construct the relative path for a user's folder
    private string GetUserFolderRelativePath(string userFolderName)
    {
        var sanitizedFolderName = Path.GetFileName(userFolderName);
        if (string.IsNullOrWhiteSpace(sanitizedFolderName) || sanitizedFolderName.Contains(".."))
        {
            throw new ArgumentException("Invalid user folder name.");
        }
        return Path.Combine(_userDataRelativePath, sanitizedFolderName).Replace(Path.DirectorySeparatorChar, '/');
    }


    // GET: /Upload/Index
    public async Task<IActionResult> Index()
    {
        var userFolderName = User.FindFirstValue("FolderName");
        if (string.IsNullOrEmpty(userFolderName))
        {
            _logger.LogWarning("User '{UserName}' attempted to access upload page without FolderName claim.", User.Identity?.Name);
            return RedirectToAction("Login", "Account");
        }

        // Get list of files currently in the user's folder using IStorageService
        var relativeFolderPath = GetUserFolderRelativePath(userFolderName);
        var files = await _storageService.ListFilesAsync(relativeFolderPath);

        ViewBag.UserFiles = files;
        _logger.LogInformation("User '{UserName}' accessed Upload page. Found {FileCount} files.", User.Identity?.Name, files.Count);

        return View();
    }

    // POST: /Upload/UploadFile (Handles single or multiple files)
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> UploadFile(List<IFormFile> files)
    {
        bool isAjax = Request.Headers["X-Requested-With"] == "XMLHttpRequest";
        var userFolderName = User.FindFirstValue("FolderName");
        var ajaxResults = new List<object>();
        int successCount = 0;
        int errorCount = 0;
        var errorMessages = new List<string>();
        var successMessages = new List<string>();

        if (string.IsNullOrEmpty(userFolderName))
        {
            _logger.LogWarning("User '{UserName}' attempted to upload file without FolderName claim.", User.Identity?.Name);
            string message = "User folder information not found.";
            if (isAjax) return Json(new { success = false, message, results = ajaxResults });
            TempData["UploadError"] = message;
            return RedirectToAction(nameof(Index));
        }

        if (files == null || !files.Any())
        {
            string message = "Please select at least one file to upload.";
            if (isAjax) return Json(new { success = false, message, results = ajaxResults });
            TempData["UploadError"] = message;
            return RedirectToAction(nameof(Index));
        }

        foreach (var file in files)
        {
            if (file == null || file.Length == 0)
            {
                errorCount++;
                string msg = "An empty file reference was received.";
                errorMessages.Add(msg);
                ajaxResults.Add(new { success = false, fileName = "N/A", message = msg });
                continue;
            }

            // --- File Validation ---
            var fileExtension = Path.GetExtension(file.FileName).ToLowerInvariant();
            string validationError = null;

            if (string.IsNullOrEmpty(fileExtension) || !AllowedExtensions.Contains(fileExtension))
            {
                validationError = $"Invalid file type ({file.FileName}). Allowed types are: {string.Join(", ", AllowedExtensions)}";
                _logger.LogWarning("User '{UserName}' attempted to upload invalid file type: {FileName}", User.Identity?.Name, file.FileName);
            }
            else if (file.Length > MaxFileSize)
            {
                validationError = $"File size exceeds the limit of {MaxFileSize / 1024 / 1024} MB ({file.FileName}).";
                _logger.LogWarning("User '{UserName}' attempted to upload oversized file: {FileName} ({FileSize} bytes)", User.Identity?.Name, file.FileName, file.Length);
            }

            if (validationError != null)
            {
                errorCount++;
                errorMessages.Add(validationError);
                ajaxResults.Add(new { success = false, fileName = file.FileName, message = validationError });
                continue;
            }
            // --- End File Validation ---

            // Use IStorageService to save
            string savedFileName = Path.GetFileName(file.FileName); // Use original name for now
            string relativePath = GetUserFileRelativePath(userFolderName, savedFileName);
            bool success = await _storageService.SaveFileAsync(relativePath, file);

            if (success)
            {
                successCount++;
                string message = $"File '{savedFileName}' uploaded successfully.";
                successMessages.Add(message);
                _logger.LogInformation("User '{UserName}' successfully uploaded file '{FileName}'.", User.Identity?.Name, savedFileName);
                ajaxResults.Add(new { success = true, message, fileName = savedFileName });
            }
            else
            {
                errorCount++;
                string message = $"An error occurred while uploading the file '{file.FileName}'.";
                errorMessages.Add(message);
                _logger.LogError("User '{UserName}' failed to upload file '{OriginalFileName}'.", User.Identity?.Name, file.FileName);
                ajaxResults.Add(new { success = false, fileName = file.FileName, message });
            }
        } // End foreach loop

        if (!isAjax)
        {
            if (successCount > 0) TempData["UploadSuccess"] = $"{successCount} file(s) uploaded successfully.";
            if (errorCount > 0) TempData["UploadError"] = $"{errorCount} file(s) failed to upload. Errors: {string.Join(" ", errorMessages)}";
            return RedirectToAction(nameof(Index));
        }
        else
        {
            bool overallSuccess = errorCount == 0;
            string summaryMessage = $"{successCount} uploaded, {errorCount} failed.";
            if (errorCount > 0) summaryMessage += $" First error: {errorMessages.FirstOrDefault()}";
            return Json(new { success = overallSuccess, message = summaryMessage, results = ajaxResults });
        }
    }

    // POST: /Upload/DeleteUploadedFile
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteUploadedFile(string fileName)
    {
        bool isAjax = Request.Headers["X-Requested-With"] == "XMLHttpRequest";
        var userFolderName = User.FindFirstValue("FolderName");
        string message;

        if (string.IsNullOrEmpty(userFolderName))
        {
             message = "User folder information not found.";
             return isAjax ? Json(new { success = false, message }) : Unauthorized(message);
        }
         if (string.IsNullOrEmpty(fileName))
        {
            message = "Invalid file name provided for deletion.";
            if(isAjax) return Json(new { success = false, message });
            TempData["UploadError"] = message;
            return RedirectToAction(nameof(Index));
        }

        // Use IStorageService to delete
        string relativePath = GetUserFileRelativePath(userFolderName, fileName);
        var success = await _storageService.DeleteFileAsync(relativePath);

        if(success)
        {
            message = $"File '{fileName}' deleted successfully.";
            _logger.LogInformation("User '{UserName}' deleted uploaded file '{FileName}'.", User.Identity?.Name, fileName);
            if(isAjax) return Json(new { success = true, message });
            TempData["UploadSuccess"] = message;
        }
        else
        {
            message = $"An error occurred while deleting file '{fileName}'.";
            _logger.LogError("User '{UserName}' failed to delete uploaded file '{FileName}'.", User.Identity?.Name, fileName);
             if(isAjax) return Json(new { success = false, message });
            TempData["UploadError"] = message;
        }
         return RedirectToAction(nameof(Index));
    }
}
