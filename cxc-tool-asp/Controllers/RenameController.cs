using cxc_tool_asp.Models;
using cxc_tool_asp.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.Rendering; // Required for SelectList
using System.Security.Claims;
using System.Text.RegularExpressions; // Required for Regex

namespace cxc_tool_asp.Controllers;

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

        // Get data needed for the view
        var files = await _fileService.GetFilesAsync(userFolderName);
        var subjects = await _subjectService.GetAllSubjectsAsync();
        var candidates = await _candidateService.GetAllCandidatesAsync(); // For datalist

        // Prepare data for dropdowns/datalists
        ViewBag.SubjectsList = new SelectList(subjects.OrderBy(s => s.Name), "CxcSubjectCode", "Name");
        // Candidate data for datalist (value will be RegNo, text could be Name + RegNo)
        ViewBag.CandidatesData = candidates
            .OrderBy(c => c.Name)
            .Select(c => new { Value = c.CxcRegistrationNo, Text = $"{c.Name} ({c.CxcRegistrationNo})" })
            .ToList();

        // Categorize files into processed and unprocessed
        var processedFiles = new List<string>();
        var unprocessedFiles = new List<string>();
        // Regex to match the naming convention: 10 digits RegNo, 8 digits SubjectCode, (CS or MS or -Number), dot, extension
        var processedFileRegex = new Regex(@"^\d{10}\d{8}(CS|MS|-\d+)\..+$"); // Updated subject code length to 8

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

    // POST: /Rename/RenameFiles
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> RenameFiles(List<string> selectedFiles, string subjectCode, string registrationNo, string docType, string centreNumber = "100111") // CentreNumber might not be needed for filename? Keeping it based on requirements text.
    {
        var userFolderName = User.FindFirstValue("FolderName");
        if (string.IsNullOrEmpty(userFolderName))
        {
            return Unauthorized("User folder information not found.");
        }

        if (selectedFiles == null || !selectedFiles.Any())
        {
            TempData["RenameError"] = "Please select at least one file to rename.";
            return RedirectToAction(nameof(Index));
        }

        if (string.IsNullOrEmpty(subjectCode) || string.IsNullOrEmpty(registrationNo) || string.IsNullOrEmpty(docType))
        {
             TempData["RenameError"] = "Please provide all renaming parameters (Subject, Candidate, Document Type).";
             return RedirectToAction(nameof(Index));
        }

        // Validate registration number format
        if (!System.Text.RegularExpressions.Regex.IsMatch(registrationNo, @"^\d{10}$"))
        {
             TempData["RenameError"] = "Invalid Candidate Registration Number format (must be 10 digits).";
             return RedirectToAction(nameof(Index));
        }
         // Validate subject code format (fetch from service to be sure?) - For now, assume valid if selected
         // Validate docType
         if (docType != "CS" && docType != "MS" && docType != "Project")
         {
              TempData["RenameError"] = "Invalid Document Type specified.";
              return RedirectToAction(nameof(Index));
         }


        int successCount = 0;
        int failCount = 0;
        List<string> errorMessages = new();
        int projectFileCounter = 1; // Counter for project files (-1, -2, ...)

        // Sort files to ensure consistent numbering for projects if multiple are selected
        selectedFiles.Sort();

        foreach (var originalFileName in selectedFiles)
        {
            string docIdentifier;
            if (docType == "Project")
            {
                docIdentifier = $"-{projectFileCounter++}";
            }
            else
            {
                docIdentifier = docType; // "CS" or "MS"
            }

            var newFileName = await _fileService.RenameFileAsync(userFolderName, originalFileName, registrationNo, subjectCode, docIdentifier);

            if (newFileName != null)
            {
                successCount++;
                _logger.LogInformation("User '{UserName}' renamed '{OriginalFile}' to '{NewFile}'.", User.Identity?.Name, originalFileName, newFileName);
            }
            else
            {
                failCount++;
                errorMessages.Add($"Failed to rename '{originalFileName}'. Possible reasons: File not found, invalid parameters, or target name already exists.");
                 _logger.LogWarning("User '{UserName}' failed to rename '{OriginalFile}'.", User.Identity?.Name, originalFileName);
            }
        }

        if (successCount > 0)
        {
            TempData["RenameSuccess"] = $"{successCount} file(s) renamed successfully.";
        }
        if (failCount > 0)
        {
            // Combine errors, potentially truncating if too many
            TempData["RenameError"] = $"{failCount} file(s) failed to rename. Errors: {string.Join(" ", errorMessages.Take(3))}{(errorMessages.Count > 3 ? "..." : "")}";
        }

        return RedirectToAction(nameof(Index));
    }


    // GET: /Rename/DownloadFile
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
    public async Task<IActionResult> DeleteFile(string fileName) // Renamed from DeleteRenamedFile for consistency
    {
        var userFolderName = User.FindFirstValue("FolderName");
        if (string.IsNullOrEmpty(userFolderName))
        {
             return Unauthorized("User folder information not found.");
        }
         if (string.IsNullOrEmpty(fileName))
        {
            TempData["RenameError"] = "Invalid file name provided for deletion.";
            return RedirectToAction(nameof(Index));
        }

        // Use the same FileService method used by UploadController and AdminController
        var success = await _fileService.DeleteFileAsync(userFolderName, fileName);

        if(success)
        {
            TempData["RenameSuccess"] = $"File '{fileName}' deleted successfully.";
             _logger.LogInformation("User '{UserName}' deleted file '{FileName}' from Rename page.", User.Identity?.Name, fileName);
        }
        else
        {
            TempData["RenameError"] = $"An error occurred while deleting file '{fileName}'.";
             _logger.LogError("User '{UserName}' failed to delete file '{FileName}' from Rename page.", User.Identity?.Name, fileName);
        }
         return RedirectToAction(nameof(Index));
    }

}
