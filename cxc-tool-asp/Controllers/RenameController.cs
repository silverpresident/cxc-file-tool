using cxc_tool_asp.Models;
using cxc_tool_asp.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.Rendering; // Required for SelectList
using System.Security.Claims;
using System.Text.RegularExpressions; // Required for Regex
using Microsoft.Net.Http.Headers; // Required for ContentDispositionHeaderValue
using MimeTypes; // Correct namespace for MimeTypeMap
using System.IO; // Required for MemoryStream, Path, FileStream
using System.Linq; // Required for Linq methods
using System.Threading.Tasks; // Required for Task
using System.Collections.Generic; // Required for List
using Microsoft.Extensions.Logging; // Added
using System; // Added
using System.IO.Compression; // Added for ZipArchive

namespace cxc_tool_asp.Controllers;

[Authorize] // Requires login
public class RenameController : Controller
{
    private readonly IStorageService _storageService;
    private readonly ICandidateService _candidateService;
    private readonly ISubjectService _subjectService;
    private readonly ILogger<RenameController> _logger;
    private readonly string _userDataRelativePath;

    public RenameController(
        IStorageService storageService,
        ICandidateService candidateService,
        ISubjectService subjectService,
        ILogger<RenameController> logger)
    {
        _storageService = storageService;
        _candidateService = candidateService;
        _subjectService = subjectService;
        _logger = logger;
        
        _userDataRelativePath = _storageService.GetDataFolderName();
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

    // GET: /Rename/Index
    public async Task<IActionResult> Index()
    {
        var userFolderName = User.FindFirstValue("FolderName");
        if (string.IsNullOrEmpty(userFolderName))
        {
            _logger.LogWarning("User '{UserName}' attempted to access rename page without FolderName claim.", User.Identity?.Name);
            return RedirectToAction("Login", "Account");
        }

        var relativeFolderPath = GetUserFolderRelativePath(userFolderName);
        var files = await _storageService.ListFilesAsync(relativeFolderPath);
        var subjects = await _subjectService.GetAllSubjectsAsync();
        var candidates = await _candidateService.GetAllCandidatesAsync();

        ViewBag.SubjectsList = new SelectList(subjects.OrderBy(s => s.Name), "CxcSubjectCode", "Name");
        ViewBag.CandidatesData = candidates
            .OrderBy(c => c.Name)
            .Select(c => new { Value = c.CandidateCode, Text = $"{c.Name} ({c.CxcRegistrationNo})" })
            .ToList();

        var processedFiles = new List<string>();
        var unprocessedFiles = new List<string>();
        var processedFileRegex = new Regex(@"^\d{10}\d{8}(CS|MS|-\d+)\..+$");

        foreach (var file in files)
        {
            if (processedFileRegex.IsMatch(file))
            {
                processedFiles.Add(file);
            }
            else
            {
                unprocessedFiles.Add(file);
            }
        }

        ViewBag.UnprocessedFiles = unprocessedFiles.OrderBy(f => f).ToList();
        ViewBag.ProcessedFiles = processedFiles.OrderBy(f => f).ToList();
        _logger.LogInformation("User '{UserName}' accessed Rename page. Found {UnprocessedCount} unprocessed and {ProcessedCount} processed files.",
            User.Identity?.Name, unprocessedFiles.Count, processedFiles.Count);

        return View();
    }

    // POST: /Rename/RenameFile (Handles single file)
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> RenameFile(string selectedFile, string subjectCode, string candidateCode, string docType, string centreNumber = "100111")
    {
        bool isAjax = Request.Headers["X-Requested-With"] == "XMLHttpRequest";
        var userFolderName = User.FindFirstValue("FolderName");
        string message;

        if (string.IsNullOrEmpty(userFolderName))
        {
            message = "User folder information not found.";
            _logger.LogWarning("User '{UserName}' attempted to rename file without FolderName claim.", User.Identity?.Name);
            return isAjax ? Json(new { success = false, message }) : Unauthorized(message);
        }

        // ... [Input validation remains the same] ...
        if (string.IsNullOrEmpty(selectedFile)) { /* ... return error ... */ }
        if (string.IsNullOrEmpty(subjectCode) || string.IsNullOrEmpty(candidateCode) || string.IsNullOrEmpty(docType) || string.IsNullOrEmpty(centreNumber)) { /* ... return error ... */ }
        if (!Regex.IsMatch(centreNumber, @"^\d{6}$")) { /* ... return error ... */ }
        if (!Regex.IsMatch(candidateCode, @"^\d{4}$")) { /* ... return error ... */ }
        if (!Regex.IsMatch(subjectCode, @"^\d{8}$")) { /* ... return error ... */ }
        if (docType != "CS" && docType != "MS" && docType != "Project") { /* ... return error ... */ }
        // --- End Validation ---

        string fullRegistrationNo = centreNumber + candidateCode;
        string docIdentifier;

        // --- Calculate Document Identifier ---
        if (docType == "Project")
        {
            try
            {
                var relativeFolderPath = GetUserFolderRelativePath(userFolderName);
                var existingFiles = await _storageService.ListFilesAsync(relativeFolderPath);
                string filePrefix = $"{fullRegistrationNo}{subjectCode}-";
                int maxNumber = 0;

                // Regex to find project files for this candidate/subject and extract number
                var projectFileRegex = new Regex($@"^{Regex.Escape(filePrefix)}(\d+)\..+$");

                foreach (var existingFile in existingFiles)
                {
                    var match = projectFileRegex.Match(existingFile);
                    if (match.Success && int.TryParse(match.Groups[1].Value, out int currentNumber))
                    {
                        if (currentNumber > maxNumber)
                        {
                            maxNumber = currentNumber;
                        }
                    }
                }
                docIdentifier = $"-{maxNumber + 1}";
            }
            catch (Exception ex)
            {
                 _logger.LogError(ex, "Error determining next project sequence number for {UserFolder}, {Prefix}", userFolderName, $"{fullRegistrationNo}{subjectCode}-");
                 message = "Error calculating project sequence number. Rename aborted.";
                 if (isAjax) return Json(new { success = false, message, oldFileName = selectedFile });
                 TempData["RenameError"] = message;
                 return RedirectToAction(nameof(Index));
            }
        }
        else
        {
            docIdentifier = docType; // "CS" or "MS"
        }
        // --- End Calculate Document Identifier ---


        // Use IStorageService to rename (MoveFile)
        string sourceRelativePath = GetUserFileRelativePath(userFolderName, selectedFile);
        string fileExtension = Path.GetExtension(selectedFile);
        string newFileName = $"{fullRegistrationNo}{subjectCode}{docIdentifier}{fileExtension}";
        string destinationRelativePath = GetUserFileRelativePath(userFolderName, newFileName);

        bool success = await _storageService.MoveFileAsync(sourceRelativePath, destinationRelativePath);

        if (success)
        {
            message = $"File '{selectedFile}' renamed to '{newFileName}' successfully.";
            _logger.LogInformation("User '{UserName}' renamed '{OriginalFile}' to '{NewFile}'.", User.Identity?.Name, selectedFile, newFileName);
            if (isAjax) return Json(new { success = true, message, oldFileName = selectedFile, newFileName });
            TempData["RenameSuccess"] = message;
        }
        else
        {
            bool destExists = await _storageService.FileExistsAsync(destinationRelativePath);
            message = $"Failed to rename '{selectedFile}'. Possible reasons: File not found, invalid parameters, or target name '{newFileName}' already exists.";
            if(destExists) message = $"Failed to rename '{selectedFile}'. Target filename '{newFileName}' already exists.";
            _logger.LogWarning("User '{UserName}' failed to rename '{OriginalFile}'. Reason: {Reason}", User.Identity?.Name, selectedFile, destExists ? "Destination exists" : "Other/Storage Error");
            if (isAjax) return Json(new { success = false, message, oldFileName = selectedFile });
            TempData["RenameError"] = message;
        }

        return RedirectToAction(nameof(Index));
    }

     // GET: /Rename/GetFileInline
     [HttpGet]
     public async Task<IActionResult> GetFileInline(string fileName)
     {
         var userFolderName = User.FindFirstValue("FolderName");
         if (string.IsNullOrEmpty(userFolderName) || string.IsNullOrEmpty(fileName)) return NotFound("Invalid parameters.");

         string relativePath = GetUserFileRelativePath(userFolderName, fileName);
         var stream = await _storageService.ReadFileAsStreamAsync(relativePath);
         if (stream == null) return NotFound($"File '{fileName}' not found.");

         var contentType = MimeTypeMap.GetMimeType(Path.GetExtension(fileName));
         var contentDisposition = new ContentDispositionHeaderValue("inline") { FileName = fileName };
         Response.Headers.Append(HeaderNames.ContentDisposition, contentDisposition.ToString());
         return File(stream, contentType);
     }


    // GET: /Rename/DownloadFile
    [HttpGet]
    public async Task<IActionResult> DownloadFile(string fileName)
    {
        var userFolderName = User.FindFirstValue("FolderName");
        if (string.IsNullOrEmpty(userFolderName) || string.IsNullOrEmpty(fileName))
        {
             TempData["RenameError"] = "Invalid file name provided.";
             return RedirectToAction(nameof(Index));
        }

        string relativePath = GetUserFileRelativePath(userFolderName, fileName);
        var stream = await _storageService.ReadFileAsStreamAsync(relativePath);
        if (stream == null)
        {
            TempData["RenameError"] = $"File '{fileName}' not found.";
            return RedirectToAction(nameof(Index));
        }
        var contentType = MimeTypeMap.GetMimeType(Path.GetExtension(fileName));
        return File(stream, contentType, fileName);
    }

    // GET: /Rename/DownloadAllFiles
    [HttpGet]
    public async Task<IActionResult> DownloadAllFiles()
    {
        var userFolderName = User.FindFirstValue("FolderName");
        if (string.IsNullOrEmpty(userFolderName)) return Unauthorized("User folder not found.");

        var relativeFolderPath = GetUserFolderRelativePath(userFolderName);
        var files = await _storageService.ListFilesAsync(relativeFolderPath);
        if (!files.Any())
        {
            TempData["RenameError"] = "No files found to download.";
            return RedirectToAction(nameof(Index));
        }

        var zipFileName = $"{userFolderName}_all_files_{DateTime.Now:yyyyMMddHHmmss}.zip";
        var memoryStream = new MemoryStream();
        try
        {
            using (var archive = new ZipArchive(memoryStream, ZipArchiveMode.Create, true))
            {
                foreach (var fileName in files)
                {
                    var relativePath = GetUserFileRelativePath(userFolderName, fileName);
                    var fileStream = await _storageService.ReadFileAsStreamAsync(relativePath);
                    if (fileStream != null)
                    {
                        var entry = archive.CreateEntry(fileName, CompressionLevel.Optimal);
                        await using (var entryStream = entry.Open()) { await fileStream.CopyToAsync(entryStream); }
                        await fileStream.DisposeAsync();
                    } else { _logger.LogWarning("File '{RelativePath}' not found during zip creation.", relativePath); }
                }
            }
            memoryStream.Position = 0;
            return File(memoryStream, "application/zip", zipFileName);
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error creating zip archive for user '{UserName}'.", User.Identity?.Name);
             TempData["RenameError"] = "Error creating zip file.";
             await memoryStream.DisposeAsync();
             return RedirectToAction(nameof(Index));
        }
    }

    // GET: /Rename/DownloadFilesBySubject
    [HttpGet]
    public async Task<IActionResult> DownloadFilesBySubject(string subjectCode)
    {
         var userFolderName = User.FindFirstValue("FolderName");
         if (string.IsNullOrEmpty(userFolderName)) return Unauthorized("User folder not found.");
         if (string.IsNullOrEmpty(subjectCode) || !Regex.IsMatch(subjectCode, @"^\d{8}$"))
         {
             TempData["RenameError"] = "Invalid subject code.";
             return RedirectToAction(nameof(Index));
         }

        var relativeFolderPath = GetUserFolderRelativePath(userFolderName);
        var allFiles = await _storageService.ListFilesAsync(relativeFolderPath);
        var filesToZip = allFiles.Where(f => f.Length > 18 && f.Substring(10, 8) == subjectCode).ToList();

        if (!filesToZip.Any())
        {
            TempData["RenameError"] = $"No files found for subject code '{subjectCode}'.";
            return RedirectToAction(nameof(Index));
        }

        var subject = await _subjectService.GetSubjectByCodeAsync(subjectCode);
        var subjectNamePart = subject?.Name.Replace(" ", "_") ?? subjectCode;
        var zipFileName = $"{userFolderName}_{subjectNamePart}_{DateTime.Now:yyyyMMddHHmmss}.zip";
        var memoryStream = new MemoryStream();
        try
        {
             using (var archive = new ZipArchive(memoryStream, ZipArchiveMode.Create, true))
            {
                foreach (var fileName in filesToZip)
                {
                    var relativePath = GetUserFileRelativePath(userFolderName, fileName);
                    var fileStream = await _storageService.ReadFileAsStreamAsync(relativePath);
                    if (fileStream != null)
                    {
                        var entry = archive.CreateEntry(fileName, CompressionLevel.Optimal);
                        await using (var entryStream = entry.Open()) { await fileStream.CopyToAsync(entryStream); }
                        await fileStream.DisposeAsync();
                    } else { _logger.LogWarning("File '{RelativePath}' not found during zip by subject.", relativePath); }
                }
            }
            memoryStream.Position = 0;
            return File(memoryStream, "application/zip", zipFileName);
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error creating zip by subject for user '{UserName}', subject '{SubjectCode}'.", User.Identity?.Name, subjectCode);
             TempData["RenameError"] = "Error creating zip file.";
             await memoryStream.DisposeAsync();
             return RedirectToAction(nameof(Index));
        }
    }

    // POST: /Rename/DeleteFile
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteFile(string fileName)
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
            message = "Invalid file name provided.";
            if(isAjax) return Json(new { success = false, message });
            TempData["RenameError"] = message;
            return RedirectToAction(nameof(Index));
        }

        string relativePath = GetUserFileRelativePath(userFolderName, fileName);
        var success = await _storageService.DeleteFileAsync(relativePath);

        if(success)
        {
            message = $"File '{fileName}' deleted successfully.";
            if(isAjax) return Json(new { success = true, message, deletedFileName = fileName });
            TempData["RenameSuccess"] = message;
        }
        else
        {
            message = $"An error occurred while deleting file '{fileName}'.";
             if(isAjax) return Json(new { success = false, message, deletedFileName = fileName });
            TempData["RenameError"] = message;
        }
         return RedirectToAction(nameof(Index));
    }
}
