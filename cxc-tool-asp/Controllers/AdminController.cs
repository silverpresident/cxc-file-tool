using cxc_tool_asp.Models;
using cxc_tool_asp.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Logging;
using System.Threading.Tasks;
using System.Collections.Generic;
using System.Linq;
using System;
using Microsoft.AspNetCore.Http;
using System.IO;
using Microsoft.AspNetCore.Hosting;
using MimeTypes; // Added for MimeTypeMap in DownloadFile

namespace cxc_tool_asp.Controllers;

[Authorize(Roles = "Admin")]
public class AdminController : Controller
{
    private readonly IUserService _userService;
    private readonly ICandidateService _candidateService;
    private readonly ISubjectService _subjectService;
    private readonly IStorageService _storageService; // Use IStorageService
    private readonly ILogger<AdminController> _logger;
    private readonly string _userDataRelativePath;

    public AdminController(
        IUserService userService,
        ICandidateService candidateService,
        ISubjectService subjectService,
        IStorageService storageService, // Inject IStorageService
        ILogger<AdminController> logger)
    {
        _userService = userService;
        _candidateService = candidateService;
        _subjectService = subjectService;
        _storageService = storageService; // Assign injected service
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

    // --- User Management ---
    [HttpGet]
    public async Task<IActionResult> Users()
    {
        var users = await _userService.GetAllUsersAsync();
        _logger.LogInformation("Admin accessed User Management page.");
        return View(users);
    }

    [HttpGet]
    public IActionResult AddUser()
    {
        return View("UserForm", new UserViewModel { DisplayName = string.Empty });
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> AddUser(UserViewModel model)
    {
        if (string.IsNullOrWhiteSpace(model.Password) || model.Password != model.ConfirmPassword)
        {
            ModelState.AddModelError("Password", "Password and confirmation password are required and must match.");
        }
        if (ModelState.IsValid)
        {
            var existingUser = await _userService.GetUserByDisplayNameAsync(model.DisplayName);
            if (existingUser != null) ModelState.AddModelError("DisplayName", "This display name is already taken.");
            else
            {
                var newUser = await _userService.AddUserAsync(model);
                if (newUser != null)
                {
                    TempData["UserMessage"] = $"User '{model.DisplayName}' added successfully.";
                    return RedirectToAction(nameof(Users));
                }
                else ModelState.AddModelError(string.Empty, "Failed to add user.");
            }
        }
        return View("UserForm", model);
    }

    [HttpGet]
    public async Task<IActionResult> EditUser(Guid id)
    {
        var user = await _userService.GetUserByIdAsync(id);
        if (user == null) return NotFound();
        var model = new UserViewModel { Id = user.Id, DisplayName = user.DisplayName, IsAdmin = user.IsAdmin };
        return View("UserForm", model);
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> EditUser(Guid id, UserViewModel model)
    {
        if (id != model.Id) return BadRequest();
        if (!string.IsNullOrWhiteSpace(model.Password) && model.Password != model.ConfirmPassword)
        {
            ModelState.AddModelError("Password", "New password and confirmation password must match.");
        }
        var originalUser = await _userService.GetUserByIdAsync(id);
        if (originalUser == null) return NotFound();

        if (ModelState.IsValid)
        {
             if (!originalUser.DisplayName.Equals(model.DisplayName, StringComparison.OrdinalIgnoreCase))
             {
                 var conflictingUser = await _userService.GetUserByDisplayNameAsync(model.DisplayName);
                 if (conflictingUser != null && conflictingUser.Id != id)
                 {
                     ModelState.AddModelError("DisplayName", "This display name is already taken.");
                 }
             }
             if (ModelState.IsValid)
             {
                var success = await _userService.UpdateUserAsync(model);
                if (success)
                {
                    TempData["UserMessage"] = $"User '{model.DisplayName}' updated successfully.";
                    return RedirectToAction(nameof(Users));
                }
                else ModelState.AddModelError(string.Empty, "Failed to update user.");
             }
        }
        return View("UserForm", model);
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteUser(Guid id)
    {
        var userToDelete = await _userService.GetUserByIdAsync(id);
        if (userToDelete == null) TempData["UserError"] = "User not found.";
        else
        {
            var success = await _userService.DeleteUserAsync(id);
            if (success) TempData["UserMessage"] = $"User '{userToDelete.DisplayName}' deleted successfully.";
            else TempData["UserError"] = $"Failed to delete user '{userToDelete.DisplayName}'.";
            // Consider deleting folder: await _storageService.DeleteDirectoryAsync(GetUserFolderRelativePath(userToDelete.FolderName));
        }
        return RedirectToAction(nameof(Users));
    }

    // --- Candidate Management ---
    [HttpGet]
    public async Task<IActionResult> Candidates()
    {
        var candidates = await _candidateService.GetAllCandidatesAsync();
        return View(candidates);
    }

     [HttpGet]
    public IActionResult AddCandidate()
    {
        return View("CandidateForm", new Candidate { Name = string.Empty, CxcRegistrationNo = string.Empty });
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> AddCandidate(Candidate model)
    {
        if (ModelState.IsValid)
        {
            var success = await _candidateService.AddCandidateAsync(model);
            if (success)
            {
                TempData["CandidateMessage"] = $"Candidate '{model.Name}' added successfully.";
                return RedirectToAction(nameof(Candidates));
            }
            else
            {
                var existing = await _candidateService.GetCandidateByRegistrationNoAsync(model.CxcRegistrationNo);
                if (existing != null) ModelState.AddModelError("CxcRegistrationNo", "Duplicate registration number.");
                else ModelState.AddModelError(string.Empty, "Failed to add candidate.");
            }
        }
        return View("CandidateForm", model);
    }

    [HttpGet]
    public async Task<IActionResult> EditCandidate(string id) // id is RegNo
    {
        var candidate = await _candidateService.GetCandidateByRegistrationNoAsync(id);
        if (candidate == null) return NotFound();
        return View("CandidateForm", candidate);
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> EditCandidate(string id, Candidate model)
    {
        if (id != model.CxcRegistrationNo) return BadRequest();
        if (ModelState.IsValid)
        {
            var success = await _candidateService.UpdateCandidateAsync(model);
            if (success)
            {
                 TempData["CandidateMessage"] = $"Candidate '{model.Name}' updated successfully.";
                 return RedirectToAction(nameof(Candidates));
            }
            else ModelState.AddModelError(string.Empty, "Failed to update candidate.");
        }
        return View("CandidateForm", model);
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteCandidate(string id) // id is RegNo
    {
        var candidateToDelete = await _candidateService.GetCandidateByRegistrationNoAsync(id);
        if (candidateToDelete == null) TempData["CandidateError"] = "Candidate not found.";
        else
        {
            var success = await _candidateService.DeleteCandidateAsync(id);
            if (success) TempData["CandidateMessage"] = $"Candidate '{candidateToDelete.Name}' deleted.";
            else TempData["CandidateError"] = $"Failed to delete candidate '{candidateToDelete.Name}'.";
        }
        return RedirectToAction(nameof(Candidates));
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteAllCandidates()
    {
        var success = await _candidateService.DeleteAllCandidatesAsync();
        if (success) TempData["CandidateMessage"] = "All candidates deleted.";
        else TempData["CandidateError"] = "Failed to delete all candidates.";
        return RedirectToAction(nameof(Candidates));
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> UploadCandidates(IFormFile candidateFile)
    {
        if (candidateFile == null || candidateFile.Length == 0) TempData["CandidateError"] = "Please select a CSV file.";
        else if (Path.GetExtension(candidateFile.FileName).ToLowerInvariant() != ".csv") TempData["CandidateError"] = "Invalid file type.";
        else
        {
            try
            {
                using var stream = candidateFile.OpenReadStream();
                var count = await _candidateService.ImportCandidatesFromCsvAsync(stream);
                if (count > 0) TempData["CandidateMessage"] = $"Imported {count} candidates.";
                else TempData["CandidateError"] = "No valid candidates found in file or import failed.";
            }
            catch (Exception ex)
            {
                 _logger.LogError(ex, "Error uploading candidate CSV.");
                 TempData["CandidateError"] = "Error during upload.";
            }
        }
        return RedirectToAction(nameof(Candidates));
    }

    [HttpGet]
    public async Task<IActionResult> DownloadCandidates()
    {
        var relativePath = _candidateService.GetCandidateFilePath();
        var stream = await _storageService.ReadFileAsStreamAsync(relativePath);
        if (stream == null)
        {
            TempData["CandidateError"] = "Candidate file not found.";
            return RedirectToAction(nameof(Candidates));
        }
        return File(stream, "text/csv", Path.GetFileName(relativePath));
    }

    // --- Subject Management ---
    [HttpGet]
    public async Task<IActionResult> Subjects()
    {
        var subjects = await _subjectService.GetAllSubjectsAsync();
        return View(subjects);
    }

     [HttpGet]
    public IActionResult AddSubject()
    {
        return View("SubjectForm", new Subject { Name = string.Empty, CxcSubjectCode = string.Empty, Level = SubjectLevel.CSEC });
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> AddSubject(Subject model)
    {
        if (ModelState.IsValid)
        {
            var success = await _subjectService.AddSubjectAsync(model);
            if (success)
            {
                TempData["SubjectMessage"] = $"Subject '{model.Name}' added successfully.";
                return RedirectToAction(nameof(Subjects));
            }
            else
            {
                 var existing = await _subjectService.GetSubjectByCodeAsync(model.CxcSubjectCode);
                 if (existing != null) ModelState.AddModelError("CxcSubjectCode", "Duplicate subject code.");
                 else ModelState.AddModelError(string.Empty, "Failed to add subject.");
            }
        }
        return View("SubjectForm", model);
    }

    [HttpGet]
    public async Task<IActionResult> EditSubject(string id) // id is SubjectCode
    {
        var subject = await _subjectService.GetSubjectByCodeAsync(id);
        if (subject == null) return NotFound();
        return View("SubjectForm", subject);
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> EditSubject(string id, Subject model)
    {
        if (id != model.CxcSubjectCode) return BadRequest();
        if (ModelState.IsValid)
        {
            var success = await _subjectService.UpdateSubjectAsync(model);
            if (success)
            {
                 TempData["SubjectMessage"] = $"Subject '{model.Name}' updated successfully.";
                 return RedirectToAction(nameof(Subjects));
            }
            else ModelState.AddModelError(string.Empty, "Failed to update subject.");
        }
        return View("SubjectForm", model);
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteSubject(string id) // id is SubjectCode
    {
        var subjectToDelete = await _subjectService.GetSubjectByCodeAsync(id);
        if (subjectToDelete == null) TempData["SubjectError"] = "Subject not found.";
        else
        {
            var success = await _subjectService.DeleteSubjectAsync(id);
            if (success) TempData["SubjectMessage"] = $"Subject '{subjectToDelete.Name}' deleted.";
            else TempData["SubjectError"] = $"Failed to delete subject '{subjectToDelete.Name}'.";
        }
        return RedirectToAction(nameof(Subjects));
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteAllSubjects()
    {
        var success = await _subjectService.DeleteAllSubjectsAsync();
        if (success) TempData["SubjectMessage"] = "All subjects deleted.";
        else TempData["SubjectError"] = "Failed to delete all subjects.";
        return RedirectToAction(nameof(Subjects));
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> UploadSubjects(IFormFile subjectFile)
    {
         if (subjectFile == null || subjectFile.Length == 0) TempData["SubjectError"] = "Please select a CSV file.";
        else if (Path.GetExtension(subjectFile.FileName).ToLowerInvariant() != ".csv") TempData["SubjectError"] = "Invalid file type.";
        else
        {
            try
            {
                using var stream = subjectFile.OpenReadStream();
                var count = await _subjectService.ImportSubjectsFromCsvAsync(stream);
                if (count > 0) TempData["SubjectMessage"] = $"Imported {count} subjects.";
                else TempData["SubjectError"] = "No valid subjects found in file or import failed.";
            }
            catch (Exception ex)
            {
                 _logger.LogError(ex, "Error uploading subject CSV.");
                 TempData["SubjectError"] = "Error during upload.";
            }
        }
        return RedirectToAction(nameof(Subjects));
    }

    [HttpGet]
    public async Task<IActionResult> DownloadSubjects()
    {
        var relativePath = _subjectService.GetSubjectFilePath();
        var stream = await _storageService.ReadFileAsStreamAsync(relativePath);
        if (stream == null)
        {
            TempData["SubjectError"] = "Subject file not found.";
            return RedirectToAction(nameof(Subjects));
        }
        return File(stream, "text/csv", Path.GetFileName(relativePath));
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> ImportDefaultSubjects([FromServices] IWebHostEnvironment env)
    {
        var localDefaultSubjectPath = Path.Combine(env.ContentRootPath, "Data2", "subjects.csv");
        var storageSubjectPath = _subjectService.GetSubjectFilePath();

        if (!System.IO.File.Exists(localDefaultSubjectPath))
        {
             _logger.LogError("Default subjects file not found: {Path}", localDefaultSubjectPath);
             TempData["SubjectError"] = "Default subjects file missing.";
             return RedirectToAction(nameof(Subjects));
        }
        try
        {
            bool success = await _storageService.CopyLocalFileToStorageAsync(localDefaultSubjectPath, storageSubjectPath, overwrite: true);
            if (success) TempData["SubjectMessage"] = "Default subjects imported successfully.";
            else TempData["SubjectError"] = "Failed to import default subjects.";
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error importing default subjects.");
             TempData["SubjectError"] = "Error during import.";
        }
        return RedirectToAction(nameof(Subjects));
    }

    // --- File Management ---
    [HttpGet]
    public async Task<IActionResult> Files()
    {
        var allFiles = new List<(string UserFolder, string FileName)>();
        try
        {
            var userFolders = await _storageService.ListSubdirectoriesAsync(_userDataRelativePath);
            foreach (var userFolder in userFolders)
            {
                var relativeFolderPath = GetUserFolderRelativePath(userFolder);
                var filesInFolder = await _storageService.ListFilesAsync(relativeFolderPath);
                allFiles.AddRange(filesInFolder.Select(f => (userFolder, f)));
            }
             _logger.LogInformation("Admin accessed File Management page. Found {Count} files.", allFiles.Count);
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error retrieving all files from storage directory {DataPath}", _userDataRelativePath);
             TempData["FileError"] = "Error retrieving file list.";
        }
        return View(allFiles);
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteFile(string userFolder, string fileName)
    {
        if (string.IsNullOrEmpty(userFolder) || string.IsNullOrEmpty(fileName))
        {
            TempData["FileError"] = "Invalid folder or file name.";
            return RedirectToAction(nameof(Files));
        }
        string relativePath = GetUserFileRelativePath(userFolder, fileName);
        var success = await _storageService.DeleteFileAsync(relativePath);
        if (success) TempData["FileMessage"] = $"File '{fileName}' deleted.";
        else TempData["FileError"] = $"Failed to delete file '{fileName}'.";
        return RedirectToAction(nameof(Files));
    }

    [HttpGet]
    public async Task<IActionResult> DownloadFile(string userFolder, string fileName)
    {
         if (string.IsNullOrEmpty(userFolder) || string.IsNullOrEmpty(fileName))
        {
            TempData["FileError"] = "Invalid folder or file name.";
            return RedirectToAction(nameof(Files));
        }
        string relativePath = GetUserFileRelativePath(userFolder, fileName);
        var stream = await _storageService.ReadFileAsStreamAsync(relativePath);
        if (stream == null)
        {
            TempData["FileError"] = $"File '{fileName}' not found.";
            return RedirectToAction(nameof(Files));
        }
        try
        {
            var contentType = MimeTypes.MimeTypeMap.GetMimeType(Path.GetExtension(fileName));
            return File(stream, contentType, fileName);
        }
        catch(Exception ex)
        {
             _logger.LogError(ex, "Error preparing file stream for download: {RelativePath}", relativePath);
             TempData["FileError"] = "Error preparing file for download.";
             await stream.DisposeAsync();
             return RedirectToAction(nameof(Files));
        }
    }
}
