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

namespace cxc_tool_asp.Controllers; // Single namespace declaration

[Authorize] // Requires login
public class RenameController : Controller
{
    private readonly IFileService _fileService;
    private readonly ICandidateService _candidateService;
    private readonly ISubjectService _subjectService;
    private readonly ILogger<RenameController> _logger;

    public RenameController(
        IFileService fileService,
        ICandidateService candidateService,
        ISubjectService subjectService,
        ILogger<RenameController> logger)
    {
        _fileService = fileService;
        _candidateService = candidateService;
        _subjectService = subjectService;
        _logger = logger;
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

        var files = await _fileService.GetFilesAsync(userFolderName);
        var subjects = await _subjectService.GetAllSubjectsAsync();
        var candidates = await _candidateService.GetAllCandidatesAsync();

        ViewBag.SubjectsList = new SelectList(subjects.OrderBy(s => s.Name), "CxcSubjectCode", "Name");
        // Candidate data for datalist - Value is now 4-digit CandidateCode
        ViewBag.CandidatesData = candidates
            .OrderBy(c => c.Name)
            .Select(c => new { Value = c.CandidateCode, Text = $"{c.Name} ({c.CxcRegistrationNo})" }) // Value is CandidateCode
            .ToList();

        var processedFiles = new List<string>();
        var unprocessedFiles = new List<string>();
        // Regex matches 10-digit RegNo + 8-digit SubjectCode + Identifier + Extension
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

        if (string.IsNullOrEmpty(selectedFile))
        {
            message = "Please select a file to rename.";
            if (isAjax) return Json(new { success = false, message });
            TempData["RenameError"] = message;
            return RedirectToAction(nameof(Index));
        }

        if (string.IsNullOrEmpty(subjectCode) || string.IsNullOrEmpty(candidateCode) || string.IsNullOrEmpty(docType) || string.IsNullOrEmpty(centreNumber))
        {
             message = "Please provide all renaming parameters (File, Subject, Centre, Candidate Code, Document Type).";
             if (isAjax) return Json(new { success = false, message });
             TempData["RenameError"] = message;
             return RedirectToAction(nameof(Index));
        }

        // Validate input formats
        if (!Regex.IsMatch(centreNumber, @"^\d{6}$"))
        {
             message = "Invalid Centre Number format (must be 6 digits).";
             if (isAjax) return Json(new { success = false, message });
             TempData["RenameError"] = message;
             return RedirectToAction(nameof(Index));
        }
        if (!Regex.IsMatch(candidateCode, @"^\d{4}$")) // Validate 4-digit candidate code
        {
             message = "Invalid Candidate Code format (must be 4 digits).";
             if (isAjax) return Json(new { success = false, message });
             TempData["RenameError"] = message;
             return RedirectToAction(nameof(Index));
        }
         if (!Regex.IsMatch(subjectCode, @"^\d{8}$")) // Check 8-digit subject code
        {
             message = "Invalid Subject Code format (must be 8 digits).";
             if (isAjax) return Json(new { success = false, message });
             TempData["RenameError"] = message;
             return RedirectToAction(nameof(Index));
        }
         if (docType != "CS" && docType != "MS" && docType != "Project")
         {
              message = "Invalid Document Type specified.";
              if (isAjax) return Json(new { success = false, message });
              TempData["RenameError"] = message;
              return RedirectToAction(nameof(Index));
         }

        // Construct the full 10-digit registration number
        string fullRegistrationNo = centreNumber + candidateCode;

        // Determine document identifier
        // For single file rename, "Project" always becomes "-1" unless logic is added later to check existing files
        string docIdentifier = (docType == "Project") ? "-1" : docType;

        var newFileName = await _fileService.RenameFileAsync(userFolderName, selectedFile, fullRegistrationNo, subjectCode, docIdentifier);

        if (newFileName != null)
        {
            message = $"File '{selectedFile}' renamed to '{newFileName}' successfully.";
            _logger.LogInformation("User '{UserName}' renamed '{OriginalFile}' to '{NewFile}'.", User.Identity?.Name, selectedFile, newFileName);
            if (isAjax) return Json(new { success = true, message, oldFileName = selectedFile, newFileName });
            TempData["RenameSuccess"] = message;
        }
        else
        {
            message = $"Failed to rename '{selectedFile}'. Possible reasons: File not found, invalid parameters, or target name already exists.";
            _logger.LogWarning("User '{UserName}' failed to rename '{OriginalFile}'.", User.Identity?.Name, selectedFile);
            if (isAjax) return Json(new { success = false, message, oldFileName = selectedFile });
            TempData["RenameError"] = message;
        }

        // For non-AJAX, redirect back to Index
        return RedirectToAction(nameof(Index));
    }

     // GET: /Rename/GetFileInline (New action for viewing)
     [HttpGet]
     public async Task<IActionResult> GetFileInline(string fileName)
     {
         var userFolderName = User.FindFirstValue("FolderName");
         if (string.IsNullOrEmpty(userFolderName) || string.IsNullOrEmpty(fileName))
         {
             return NotFound("Invalid request parameters.");
         }

         var filePath = _fileService.GetFilePath(userFolderName, fileName);
         if (filePath == null || !System.IO.File.Exists(filePath))
         {
              _logger.LogWarning("User '{UserName}' attempted to view non-existent file '{FileName}'.", User.Identity?.Name, fileName);
             return NotFound($"File '{fileName}' not found.");
         }

         var memoryStream = new MemoryStream();
         await using (var stream = new FileStream(filePath, FileMode.Open, FileAccess.Read))
         {
             await stream.CopyToAsync(memoryStream);
         }
         memoryStream.Position = 0;

         var contentType = MimeTypeMap.GetMimeType(Path.GetExtension(fileName)); // Use correct class

         // Set Content-Disposition to inline
         var contentDisposition = new ContentDispositionHeaderValue("inline")
         {
             FileName = fileName // Use the actual filename
         };
         Response.Headers.Append(HeaderNames.ContentDisposition, contentDisposition.ToString());

         _logger.LogInformation("User '{UserName}' viewed file inline '{FileName}'.", User.Identity?.Name, fileName);
         return File(memoryStream, contentType);
     }


    // GET: /Rename/DownloadFile
    [HttpGet]
    public async Task<IActionResult> DownloadFile(string fileName)
    {
        var userFolderName = User.FindFirstValue("FolderName");
        if (string.IsNullOrEmpty(userFolderName))
        {
            return Unauthorized("User folder information not found.");
        }
         if (string.IsNullOrEmpty(fileName))
        {
            TempData["RenameError"] = "Invalid file name provided for download.";
            return RedirectToAction(nameof(Index));
        }

        var fileResult = await _fileService.GetFileDownloadAsync(userFolderName, fileName);

        if (fileResult == null)
        {
            TempData["RenameError"] = $"File '{fileName}' not found or could not be prepared for download.";
            _logger.LogWarning("User '{UserName}' failed to download file '{FileName}'. File not found or error.", User.Identity?.Name, fileName);
            return RedirectToAction(nameof(Index));
        }

         _logger.LogInformation("User '{UserName}' downloaded file '{FileName}'.", User.Identity?.Name, fileName);
        return fileResult;
    }

    // GET: /Rename/DownloadAllFiles
    [HttpGet]
    public async Task<IActionResult> DownloadAllFiles()
    {
        var userFolderName = User.FindFirstValue("FolderName");
        if (string.IsNullOrEmpty(userFolderName))
        {
            return Unauthorized("User folder information not found.");
        }

        var files = await _fileService.GetFilesAsync(userFolderName);
        if (!files.Any())
        {
            TempData["RenameError"] = "No files found to download.";
            return RedirectToAction(nameof(Index));
        }

        var zipFileName = $"{userFolderName}_all_files_{DateTime.Now:yyyyMMddHHmmss}";
        var zipStream = await _fileService.CreateZipArchiveAsync(userFolderName, files, zipFileName);

        if (zipStream == null)
        {
            TempData["RenameError"] = "An error occurred while creating the zip file.";
             _logger.LogError("Failed to create zip archive for user '{UserName}'.", User.Identity?.Name);
            return RedirectToAction(nameof(Index));
        }

        _logger.LogInformation("User '{UserName}' downloaded all files as '{ZipFileName}.zip'.", User.Identity?.Name, zipFileName);
        return File(zipStream, "application/zip", $"{zipFileName}.zip");
    }

    // GET: /Rename/DownloadFilesBySubject
    [HttpGet]
    public async Task<IActionResult> DownloadFilesBySubject(string subjectCode)
    {
         var userFolderName = User.FindFirstValue("FolderName");
        if (string.IsNullOrEmpty(userFolderName))
        {
            return Unauthorized("User folder information not found.");
        }
         if (string.IsNullOrEmpty(subjectCode))
        {
            TempData["RenameError"] = "Please select a subject to download files for.";
            return RedirectToAction(nameof(Index));
        }

        var allFiles = await _fileService.GetFilesAsync(userFolderName);
        // Filter files based on the naming convention: {RegNo}{SubjectCode}{DocId}.ext
        // We expect the 8-digit subject code right after the 10-digit registration number.
        var filesToZip = allFiles.Where(f => f.Length > 18 && f.Substring(10, 8) == subjectCode).ToList(); // 10 digits for RegNo, 8 for SubjectCode

        if (!filesToZip.Any())
        {
            TempData["RenameError"] = $"No files found for subject code '{subjectCode}'. Ensure files are renamed correctly.";
            return RedirectToAction(nameof(Index));
        }

        var subject = await _subjectService.GetSubjectByCodeAsync(subjectCode);
        var subjectNamePart = subject?.Name.Replace(" ", "_") ?? subjectCode; // Use subject name for zip file if available
        var zipFileName = $"{userFolderName}_{subjectNamePart}_{DateTime.Now:yyyyMMddHHmmss}";
        var zipStream = await _fileService.CreateZipArchiveAsync(userFolderName, filesToZip, zipFileName);

         if (zipStream == null)
        {
            TempData["RenameError"] = "An error occurred while creating the zip file for the selected subject.";
             _logger.LogError("Failed to create zip archive for user '{UserName}', subject '{SubjectCode}'.", User.Identity?.Name, subjectCode);
            return RedirectToAction(nameof(Index));
        }

        _logger.LogInformation("User '{UserName}' downloaded files for subject '{SubjectCode}' as '{ZipFileName}.zip'.", User.Identity?.Name, subjectCode, zipFileName);
        return File(zipStream, "application/zip", $"{zipFileName}.zip");
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
            message = "Invalid file name provided for deletion.";
            if(isAjax) return Json(new { success = false, message });
            TempData["RenameError"] = message;
            return RedirectToAction(nameof(Index));
        }

        var success = await _fileService.DeleteFileAsync(userFolderName, fileName);

        if(success)
        {
            message = $"File '{fileName}' deleted successfully.";
            _logger.LogInformation("User '{UserName}' deleted file '{FileName}' from Rename page.", User.Identity?.Name, fileName);
            if(isAjax) return Json(new { success = true, message, deletedFileName = fileName });
            TempData["RenameSuccess"] = message;
        }
        else
        {
            message = $"An error occurred while deleting file '{fileName}'.";
            _logger.LogError("User '{UserName}' failed to delete file '{FileName}' from Rename page.", User.Identity?.Name, fileName);
             if(isAjax) return Json(new { success = false, message, deletedFileName = fileName });
            TempData["RenameError"] = message;
        }
         // For non-AJAX, redirect back to Index
         return RedirectToAction(nameof(Index));
    }

}
