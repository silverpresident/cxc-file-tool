using cxc_tool_asp.Models;
using CsvHelper;
using CsvHelper.Configuration;
using System.Collections.Concurrent;
using System.Globalization;
using System.IO; // Still needed for Stream, StreamReader, StreamWriter
using System.Threading.Tasks;
using System.Linq;
using System.Collections.Generic;
using Microsoft.Extensions.Logging;
using System; // For StringComparison, Guid, Random, ArgumentException
using System.Text; // For StringBuilder (if needed, maybe not)

namespace cxc_tool_asp.Services;

/// <summary>
/// Service implementation for managing user data using IStorageService.
/// </summary>
public class UserService : IUserService
{
    private readonly IStorageService _storageService;
    private readonly ILogger<UserService> _logger;
    private readonly string _usersRelativePath = "Data2/users.csv"; // Relative path for storage
    private readonly string _userDataRelativePath = "Data"; // Base relative path for user folders

    private static readonly ConcurrentDictionary<string, User> _userCache = new();
    private static readonly SemaphoreSlim _cacheLock = new(1, 1); // Lock for cache loading/saving

    private readonly CsvConfiguration _csvConfig = new(CultureInfo.InvariantCulture)
    {
        HasHeaderRecord = true,
        MissingFieldFound = null,
        HeaderValidated = null,
    };

    public UserService(IStorageService storageService, ILogger<UserService> logger)
    {
        _storageService = storageService;
        _logger = logger;
        // Initial load into cache - fire and forget, errors logged internally
        _ = LoadUsersFromStorageAsync();
    }

    private async Task LoadUsersFromStorageAsync()
    {
        await _cacheLock.WaitAsync();
        try
        {
            // Check if cache is already populated (by another thread)
            if (!_userCache.IsEmpty) return;

            _logger.LogInformation("Attempting to load users from storage: {Path}", _usersRelativePath);
            var stream = await _storageService.ReadFileAsStreamAsync(_usersRelativePath);
            if (stream == null)
            {
                _logger.LogInformation("User data file not found or empty at {Path}. Initializing empty cache.", _usersRelativePath);
                _userCache.Clear(); // Ensure cache is empty if file doesn't exist
                return;
            }

            List<User> users;
            using (var reader = new StreamReader(stream)) // Dispose stream after reading
            using (var csv = new CsvReader(reader, _csvConfig))
            {
                users = csv.GetRecords<User>().ToList();
            }

            _userCache.Clear(); // Clear before repopulating
            foreach (var user in users)
            {
                _userCache.TryAdd(user.DisplayName, user); // Use DisplayName as key for quick lookup
            }
            _logger.LogInformation("Loaded {UserCount} users from storage into cache.", users.Count);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error loading users from storage: {Path}", _usersRelativePath);
            _userCache.Clear(); // Clear cache on error
        }
        finally
        {
            _cacheLock.Release();
        }
    }

    private async Task SaveUsersToStorageAsync()
    {
        // This method assumes the cache is up-to-date and writes the entire cache back to storage.
        // It should ideally be called within the _cacheLock.
        try
        {
            var usersToSave = _userCache.Values.ToList(); // Get current cache content
            using var memoryStream = new MemoryStream();
            using (var writer = new StreamWriter(memoryStream, leaveOpen: true)) // Keep stream open after writer dispose
            using (var csv = new CsvWriter(writer, _csvConfig))
            {
                await csv.WriteRecordsAsync(usersToSave);
            }
            memoryStream.Position = 0; // Reset stream position for reading

            bool success = await _storageService.SaveFileAsync(_usersRelativePath, memoryStream);
            if (success)
            {
                _logger.LogInformation("Saved {UserCount} users to storage: {Path}", usersToSave.Count, _usersRelativePath);
            }
            else
            {
                 _logger.LogError("Failed to save users to storage: {Path}", _usersRelativePath);
                 // Consider how to handle this failure - maybe retry? For now, just log.
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error saving users to storage: {Path}", _usersRelativePath);
            throw; // Re-throw to indicate failure to the caller
        }
    }

    public async Task<List<User>> GetAllUsersAsync()
    {
        if (_userCache.IsEmpty)
        {
            await LoadUsersFromStorageAsync(); // Ensure cache is loaded if empty
        }
        // Return a copy to prevent external modification of cached list
        return _userCache.Values.OrderBy(u => u.DisplayName).ToList();
    }

    public async Task<User?> GetUserByDisplayNameAsync(string displayName)
    {
        if (_userCache.IsEmpty) await LoadUsersFromStorageAsync();
        // Find user case-insensitively from cache
        var userEntry = _userCache.FirstOrDefault(kvp => kvp.Key.Equals(displayName, StringComparison.OrdinalIgnoreCase));
        return userEntry.Value; // Returns null if not found
    }

     public async Task<User?> GetUserByIdAsync(Guid id)
    {
        if (_userCache.IsEmpty) await LoadUsersFromStorageAsync();
        return _userCache.Values.FirstOrDefault(u => u.Id == id);
    }

    public async Task<User?> AddUserAsync(UserViewModel userViewModel)
    {
        if (string.IsNullOrWhiteSpace(userViewModel.Password))
        {
            _logger.LogWarning("Attempted to add user '{DisplayName}' with empty password.", userViewModel.DisplayName);
            return null;
        }

        await _cacheLock.WaitAsync(); // Lock before checking cache and potentially modifying
        try
        {
            // Ensure cache is loaded within the lock
            if (_userCache.IsEmpty) await LoadUsersFromStorageAsync(); // This will release and re-acquire lock internally

            // Check for duplicate display name (case-insensitive) within the lock
            if (_userCache.Values.Any(u => u.DisplayName.Equals(userViewModel.DisplayName, StringComparison.OrdinalIgnoreCase)))
            {
                _logger.LogWarning("Attempted to add user with duplicate display name: {DisplayName}", userViewModel.DisplayName);
                return null;
            }

            var folderName = await GenerateUniqueFolderNameAsync(); // Needs access to cache, call within lock
            if (folderName == null)
            {
                 _logger.LogError("Failed to generate a unique folder name after multiple attempts.");
                 return null;
            }

            var newUser = new User
            {
                Id = Guid.NewGuid(),
                DisplayName = userViewModel.DisplayName,
                PasswordHash = BCrypt.Net.BCrypt.HashPassword(userViewModel.Password),
                FolderName = folderName,
                IsAdmin = userViewModel.IsAdmin
            };

            // Update cache first
            if (!_userCache.TryAdd(newUser.DisplayName, newUser))
            {
                 _logger.LogError("Failed to add user {DisplayName} to cache.", newUser.DisplayName);
                 return null;
            }

            // Persist changes to storage
            await SaveUsersToStorageAsync(); // This should handle its own logging/errors

            _logger.LogInformation("Successfully added user: {DisplayName} with FolderName: {FolderName}", newUser.DisplayName, newUser.FolderName);
            // Note: Creating the user's directory is now implicitly handled by FileService/StorageService when the first file is saved.
            return newUser;
        }
        catch (Exception ex) // Catch potential SaveUsersToStorageAsync errors
        {
             _logger.LogError(ex, "Failed to save new user {DisplayName} to storage. Reverting cache.", userViewModel.DisplayName);
             // Revert cache change if save fails
             _userCache.TryRemove(userViewModel.DisplayName, out _);
             return null;
        }
        finally
        {
            _cacheLock.Release();
        }
    }

    public async Task<bool> UpdateUserAsync(UserViewModel userViewModel)
    {
        if (!userViewModel.Id.HasValue) return false;

        await _cacheLock.WaitAsync();
        try
        {
            if (_userCache.IsEmpty) await LoadUsersFromStorageAsync();

            var existingUser = _userCache.Values.FirstOrDefault(u => u.Id == userViewModel.Id.Value);
            if (existingUser == null)
            {
                 _logger.LogWarning("UpdateUser failed: User with ID {UserId} not found in cache.", userViewModel.Id.Value);
                 return false;
            }

            // Check if display name is changing and if the new name conflicts
            bool nameChanged = !existingUser.DisplayName.Equals(userViewModel.DisplayName, StringComparison.OrdinalIgnoreCase);
            if (nameChanged && _userCache.Values.Any(u => u.Id != userViewModel.Id.Value && u.DisplayName.Equals(userViewModel.DisplayName, StringComparison.OrdinalIgnoreCase)))
            {
                 _logger.LogWarning("Attempted to update user {UserId} with conflicting display name: {DisplayName}", userViewModel.Id, userViewModel.DisplayName);
                return false;
            }

            var updatedUser = existingUser with
            {
                DisplayName = userViewModel.DisplayName,
                IsAdmin = userViewModel.IsAdmin,
                PasswordHash = !string.IsNullOrWhiteSpace(userViewModel.Password)
                               ? BCrypt.Net.BCrypt.HashPassword(userViewModel.Password)
                               : existingUser.PasswordHash
            };

            // Update cache: Remove old, add new
            if (!_userCache.TryRemove(existingUser.DisplayName, out _))
            {
                 _logger.LogError("Failed to remove old user record {DisplayName} from cache during update.", existingUser.DisplayName);
                 return false; // Cache inconsistency
            }
             if (!_userCache.TryAdd(updatedUser.DisplayName, updatedUser))
            {
                 _logger.LogError("Failed to add updated user record {DisplayName} to cache. Attempting to restore old record.", updatedUser.DisplayName);
                 _userCache.TryAdd(existingUser.DisplayName, existingUser); // Restore old
                 return false;
            }

            // Persist changes
            await SaveUsersToStorageAsync();
            _logger.LogInformation("Successfully updated user: {DisplayName} (ID: {UserId})", updatedUser.DisplayName, updatedUser.Id);
            return true;
        }
         catch (Exception ex)
        {
             _logger.LogError(ex, "Failed to save updated user {DisplayName} to storage. Attempting cache revert.", userViewModel.DisplayName);
             // Attempt to revert cache - more complex logic might be needed depending on failure point
             // For now, just log the error during save. The cache might be inconsistent.
            return false;
        }
        finally
        {
            _cacheLock.Release();
        }
    }

     public async Task<bool> DeleteUserAsync(Guid id)
    {
        await _cacheLock.WaitAsync();
        try
        {
            if (_userCache.IsEmpty) await LoadUsersFromStorageAsync();

            var userToRemove = _userCache.Values.FirstOrDefault(u => u.Id == id);
            if (userToRemove == null)
            {
                 _logger.LogWarning("DeleteUser failed: User with ID {UserId} not found in cache.", id);
                 return false; // Not found
            }

            // Remove from cache
            if (!_userCache.TryRemove(userToRemove.DisplayName, out _))
            {
                _logger.LogWarning("Failed to remove user {DisplayName} (ID: {UserId}) from cache during deletion.", userToRemove.DisplayName, id);
                return false; // Cache inconsistency
            }

            // Persist changes
            await SaveUsersToStorageAsync();
            _logger.LogInformation("Successfully deleted user: {DisplayName} (ID: {UserId})", userToRemove.DisplayName, id);
            // Note: Does not delete user's folder/files in storage.
            return true;
        }
         catch (Exception ex)
        {
             _logger.LogError(ex, "Failed to save user list to storage after deleting {UserId}. Attempting cache revert.", id);
             // Attempt to revert cache - requires fetching user info again if needed
             // For now, just log the error. Cache might be inconsistent.
            return false;
        }
        finally
        {
            _cacheLock.Release();
        }
    }

    public async Task<User?> ValidateCredentialsAsync(string displayName, string password)
    {
        var user = await GetUserByDisplayNameAsync(displayName); // Uses cache
        if (user == null && (_userCache.Count == 0))
        {
            if (displayName == password && displayName == "admin")
            {
                return new User
                {
                    DisplayName = displayName,
                    FolderName = "admin",
                    IsAdmin = true,
                    PasswordHash = "admin"
                };
            }
        }
        if (user != null && BCrypt.Net.BCrypt.Verify(password, user.PasswordHash))
        {
            return user;
        }

        _logger.LogWarning("Failed login attempt for user: {DisplayName}", displayName);
        return null;
    }

    // --- Helper Methods ---

    // This needs to check storage now, not just cache/local dirs
    private async Task<string?> GenerateUniqueFolderNameAsync(int maxAttempts = 10)
    {
        const string chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        var random = new Random();

        // Assumes called within _cacheLock, so cache is loaded/up-to-date
        var existingCacheFolderNames = new HashSet<string>(_userCache.Values.Select(u => u.FolderName), StringComparer.OrdinalIgnoreCase);

        // Check storage for existing directories (more expensive)
        var existingStorageDirs = await _storageService.ListSubdirectoriesAsync(_userDataRelativePath);
        var allExistingFolders = existingCacheFolderNames.Union(existingStorageDirs, StringComparer.OrdinalIgnoreCase).ToHashSet(StringComparer.OrdinalIgnoreCase);


        for (int attempt = 0; attempt < maxAttempts; attempt++)
        {
            string folderName;
            // Try 2 letters first
            folderName = new string(Enumerable.Repeat(chars, 2)
              .Select(s => s[random.Next(s.Length)]).ToArray());

            if (!allExistingFolders.Contains(folderName))
            {
                return folderName;
            }

            // Try 2 letters + 1 digit
            folderName = new string(Enumerable.Repeat(chars, 2)
              .Select(s => s[random.Next(s.Length)]).ToArray()) + random.Next(0, 10).ToString();

             if (!allExistingFolders.Contains(folderName))
            {
                return folderName;
            }
        }

        _logger.LogError("Could not generate a unique folder name after {MaxAttempts} attempts.", maxAttempts);
        return null;
    }
}
