using Microsoft.AspNetCore.Hosting; // For IWebHostEnvironment
using Microsoft.AspNetCore.Http; // For IFormFile
using Microsoft.Extensions.Logging;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading.Tasks;

namespace cxc_tool_asp.Services;

/// <summary>
/// Implements IStorageService using the local file system.
/// </summary>
public class LocalStorageService : IStorageService
{
    private readonly string _basePath; // The root directory (e.g., wwwroot or ContentRootPath)
    private readonly ILogger<LocalStorageService> _logger;

    public LocalStorageService(IWebHostEnvironment env, ILogger<LocalStorageService> logger)
    {
        // Use ContentRootPath as the base for Data and Data2 folders
        _basePath = env.ContentRootPath;
        _logger = logger;
        // Ensure base directories exist (although services might also do this)
        Directory.CreateDirectory(Path.Combine(_basePath, "Data"));
        Directory.CreateDirectory(Path.Combine(_basePath, "Data2"));
    }

    private string GetFullPath(string relativePath)
    {
        // Combine with base path and normalize separators
        var fullPath = Path.Combine(_basePath, relativePath.TrimStart('/', '\\').Replace('/', Path.DirectorySeparatorChar));
        // Basic security check: Ensure the path is still within the base path
        if (!fullPath.StartsWith(_basePath, StringComparison.OrdinalIgnoreCase))
        {
            throw new ArgumentException("Invalid relative path specified.", nameof(relativePath));
        }
        return fullPath;
    }

    public async Task<bool> SaveFileAsync(string relativePath, Stream stream)
    {
        try
        {
            var fullPath = GetFullPath(relativePath);
            var directory = Path.GetDirectoryName(fullPath);
            if (!string.IsNullOrEmpty(directory))
            {
                Directory.CreateDirectory(directory); // Ensure directory exists
            }

            await using var fileStream = new FileStream(fullPath, FileMode.Create, FileAccess.Write);
            stream.Position = 0; // Ensure stream is at the beginning
            await stream.CopyToAsync(fileStream);
            _logger.LogDebug("Saved stream to local path: {FullPath}", fullPath);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error saving stream to local path: {RelativePath}", relativePath);
            return false;
        }
    }

    public async Task<bool> SaveFileAsync(string relativePath, IFormFile formFile)
    {
         try
        {
            var fullPath = GetFullPath(relativePath);
            var directory = Path.GetDirectoryName(fullPath);
            if (!string.IsNullOrEmpty(directory))
            {
                Directory.CreateDirectory(directory);
            }

            await using var fileStream = new FileStream(fullPath, FileMode.Create, FileAccess.Write);
            await formFile.CopyToAsync(fileStream);
             _logger.LogDebug("Saved IFormFile '{FileName}' to local path: {FullPath}", formFile.FileName, fullPath);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error saving IFormFile '{FileName}' to local path: {RelativePath}", formFile.FileName, relativePath);
            return false;
        }
    }

    public Task<Stream?> ReadFileAsStreamAsync(string relativePath)
    {
        try
        {
            var fullPath = GetFullPath(relativePath);
            if (!File.Exists(fullPath))
            {
                _logger.LogWarning("Attempted to read non-existent local file as stream: {FullPath}", fullPath);
                return Task.FromResult<Stream?>(null);
            }
            // Open for read, allow sharing for read
            var stream = new FileStream(fullPath, FileMode.Open, FileAccess.Read, FileShare.Read);
            return Task.FromResult<Stream?>(stream);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error reading local file as stream: {RelativePath}", relativePath);
            return Task.FromResult<Stream?>(null);
        }
    }

     public async Task<string?> ReadFileAsTextAsync(string relativePath)
    {
        try
        {
            var fullPath = GetFullPath(relativePath);
            if (!File.Exists(fullPath))
            {
                 _logger.LogWarning("Attempted to read non-existent local file as text: {FullPath}", fullPath);
                return null;
            }
            return await File.ReadAllTextAsync(fullPath);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error reading local file as text: {RelativePath}", relativePath);
            return null;
        }
    }

    public Task<bool> DeleteFileAsync(string relativePath)
    {
        try
        {
            var fullPath = GetFullPath(relativePath);
            if (File.Exists(fullPath))
            {
                File.Delete(fullPath);
                _logger.LogDebug("Deleted local file: {FullPath}", fullPath);
            }
            return Task.FromResult(true); // Success even if file didn't exist
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error deleting local file: {RelativePath}", relativePath);
            return Task.FromResult(false);
        }
    }

    public Task<bool> FileExistsAsync(string relativePath)
    {
        try
        {
            var fullPath = GetFullPath(relativePath);
            return Task.FromResult(File.Exists(fullPath));
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error checking local file existence: {RelativePath}", relativePath);
             return Task.FromResult(false);
        }
    }

    public Task<bool> MoveFileAsync(string sourceRelativePath, string destinationRelativePath)
    {
         try
        {
            var sourceFullPath = GetFullPath(sourceRelativePath);
            var destinationFullPath = GetFullPath(destinationRelativePath);

            if (!File.Exists(sourceFullPath))
            {
                 _logger.LogWarning("MoveFile failed: Source file not found: {SourcePath}", sourceFullPath);
                 return Task.FromResult(false);
            }
             if (File.Exists(destinationFullPath))
            {
                 _logger.LogWarning("MoveFile failed: Destination file already exists: {DestinationPath}", destinationFullPath);
                 return Task.FromResult(false);
            }

             var destDir = Path.GetDirectoryName(destinationFullPath);
             if(!string.IsNullOrEmpty(destDir)) Directory.CreateDirectory(destDir);

            File.Move(sourceFullPath, destinationFullPath);
             _logger.LogDebug("Moved local file from {SourcePath} to {DestinationPath}", sourceFullPath, destinationFullPath);
            return Task.FromResult(true);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error moving local file from {SourceRelativePath} to {DestinationRelativePath}", sourceRelativePath, destinationRelativePath);
            return Task.FromResult(false);
        }
    }

    public Task<List<string>> ListFilesAsync(string relativeDirectoryPath)
    {
        var files = new List<string>();
        try
        {
            var fullPath = GetFullPath(relativeDirectoryPath);
            if (Directory.Exists(fullPath))
            {
                files.AddRange(Directory.GetFiles(fullPath).Select(Path.GetFileName));
            }
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error listing local files in directory: {RelativeDirectoryPath}", relativeDirectoryPath);
             // Return empty list on error
        }
        return Task.FromResult(files);
    }

     public Task<List<string>> ListSubdirectoriesAsync(string relativeDirectoryPath)
    {
        var dirs = new List<string>();
        try
        {
            var fullPath = GetFullPath(relativeDirectoryPath);
            if (Directory.Exists(fullPath))
            {
                dirs.AddRange(Directory.GetDirectories(fullPath).Select(Path.GetFileName));
            }
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error listing local subdirectories in directory: {RelativeDirectoryPath}", relativeDirectoryPath);
             // Return empty list on error
        }
        return Task.FromResult(dirs);
    }

    public Task<bool> DeleteDirectoryAsync(string relativeDirectoryPath)
    {
         try
        {
            var fullPath = GetFullPath(relativeDirectoryPath);
            if (Directory.Exists(fullPath))
            {
                Directory.Delete(fullPath, true); // Recursive delete
                _logger.LogDebug("Deleted local directory recursively: {FullPath}", fullPath);
            }
            return Task.FromResult(true); // Success even if directory didn't exist
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error deleting local directory: {RelativeDirectoryPath}", relativeDirectoryPath);
            return Task.FromResult(false);
        }
    }

    public string? GetFileUrl(string relativePath)
    {
        // Local files don't typically have a direct public URL accessible this way
        return null;
    }

     public Task<bool> CopyLocalFileToStorageAsync(string localSourcePath, string destinationRelativePath, bool overwrite = false)
     {
         try
         {
             var destinationFullPath = GetFullPath(destinationRelativePath);

             if (!File.Exists(localSourcePath))
             {
                 _logger.LogError("CopyLocalFile failed: Source file not found: {SourcePath}", localSourcePath);
                 return Task.FromResult(false);
             }
             if (!overwrite && File.Exists(destinationFullPath))
             {
                  _logger.LogWarning("CopyLocalFile skipped: Destination file already exists and overwrite is false: {DestinationPath}", destinationFullPath);
                  return Task.FromResult(true); // Considered success if not overwriting existing
             }

             var destDir = Path.GetDirectoryName(destinationFullPath);
             if(!string.IsNullOrEmpty(destDir)) Directory.CreateDirectory(destDir);

             File.Copy(localSourcePath, destinationFullPath, overwrite);
             _logger.LogDebug("Copied local file from {SourcePath} to {DestinationPath}", localSourcePath, destinationFullPath);
             return Task.FromResult(true);
         }
         catch (Exception ex)
         {
              _logger.LogError(ex, "Error copying local file from {SourcePath} to {DestinationRelativePath}", localSourcePath, destinationRelativePath);
             return Task.FromResult(false);
         }
     }
}
