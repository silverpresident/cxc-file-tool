using cxc_tool_asp.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using System.Security.Claims; // Required for User.FindFirstValue
using System.Collections.Generic; // Required for List
using System.Threading.Tasks; // Required for Task
using Microsoft.AspNetCore.Http; // Required for IFormFile
using System.IO; // Required for Path
using System.Linq; // Required for Linq methods like Any()

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
            return RedirectToAction("Login", "Account");
        }

        var files = await _fileService.GetFilesAsync(userFolderName);
        ViewBag.UserFiles = files;
        _logger.LogInformation("User '{UserName}' accessed Upload page. Found {FileCount} files.", User.Identity?.Name, files.Count);

        return View();
    }

    // POST: /Upload/UploadFile (Handles single or multiple files)
    [HttpPost]
    [ValidateAntiForgeryToken]
    // Adjust RequestSizeLimit based on expected total size of multiple files if needed
    // [RequestSizeLimit(MaxFileSize * 5 + 1024)] // Example: Allow up to 5 files of max size + overhead
    public async Task<IActionResult> UploadFile(List<IFormFile> files) // Changed parameter to List<IFormFile>
    {
        bool isAjax = Request.Headers["X-Requested-With"] == "XMLHttpRequest";
        var userFolderName = User.FindFirstValue("FolderName");

        // Return structure for AJAX response when handling multiple files
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
                errorMessages.Add("An empty file reference was received.");
                ajaxResults.Add(new { success = false, fileName = "N/A", message = "Empty file reference." });
                continue; // Skip to the next file
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
                continue; // Skip to the next file
            }
            // --- End File Validation ---

            var savedFileName = await _fileService.SaveFileAsync(userFolderName, file);

            if (savedFileName != null)
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

        // Set TempData for non-AJAX requests or summary for AJAX
        if (!isAjax)
        {
            if (successCount > 0) TempData["UploadSuccess"] = $"{successCount} file(s) uploaded successfully.";
            if (errorCount > 0) TempData["UploadError"] = $"{errorCount} file(s) failed to upload. Errors: {string.Join(" ", errorMessages)}";
            return RedirectToAction(nameof(Index));
        }
        else
        {
            // For AJAX, return a summary and detailed results
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
        if (string.IsNullOrEmpty(userFolderName))
        {
             return isAjax ? Json(new { success = false, message = "User folder information not found." }) : Unauthorized("User folder information not found.");
        }
         if (string.IsNullOrEmpty(fileName))
        {
            string message = "Invalid file name provided for deletion.";
            if(isAjax) return Json(new { success = false, message });
            TempData["UploadError"] = message;
            return RedirectToAction(nameof(Index));
        }

        var success = await _fileService.DeleteFileAsync(userFolderName, fileName);

        if(success)
        {
            string message = $"File '{fileName}' deleted successfully.";
            _logger.LogInformation("User '{UserName}' deleted uploaded file '{FileName}'.", User.Identity?.Name, fileName);
            if(isAjax) return Json(new { success = true, message });
            TempData["UploadSuccess"] = message;
        }
        else
        {
            string message = $"An error occurred while deleting file '{fileName}'.";
            _logger.LogError("User '{UserName}' failed to delete uploaded file '{FileName}'.", User.Identity?.Name, fileName);
             if(isAjax) return Json(new { success = false, message });
            TempData["UploadError"] = message;
        }
         return RedirectToAction(nameof(Index));
    }

}
