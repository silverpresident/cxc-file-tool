using cxc_tool_asp.Models; // Add model namespace
using cxc_tool_asp.Services;
using Microsoft.AspNetCore.Authentication.Cookies;
using Microsoft.Extensions.DependencyInjection; // Required for GetRequiredService
using Microsoft.Extensions.Logging; // Required for ILogger

namespace cxc_tool_asp;

public class Program
{
    // Change Main to async Task to allow await
    public static async Task Main(string[] args)
    {
        var builder = WebApplication.CreateBuilder(args);

        // Add services to the container.
        builder.Services.AddControllersWithViews();

        // Register application services (using Singleton for file-based services with locking/caching)
        builder.Services.AddSingleton<IUserService, UserService>();
        builder.Services.AddSingleton<ICandidateService, CandidateService>();
        builder.Services.AddSingleton<ISubjectService, SubjectService>();
        builder.Services.AddSingleton<IFileService, FileService>();

        // Configure Cookie Authentication
        builder.Services.AddAuthentication(CookieAuthenticationDefaults.AuthenticationScheme)
            .AddCookie(options =>
            {
                options.LoginPath = "/Account/Login"; // Set the login path
                options.ExpireTimeSpan = TimeSpan.FromMinutes(30); // Set cookie expiration
                options.SlidingExpiration = true; // Renew cookie on activity
                options.AccessDeniedPath = "/Account/AccessDenied"; // Optional: Path for access denied
            });

        // Add HttpContextAccessor for accessing HttpContext in services if needed (though generally avoided)
        builder.Services.AddHttpContextAccessor();


        var app = builder.Build();

        // --- Check for Admin Creation Argument ---
        if (args.Contains("--create-admin", StringComparer.OrdinalIgnoreCase))
        {
            // Get services needed for admin creation
            using var scope = app.Services.CreateScope();
            var services = scope.ServiceProvider;
            var userService = services.GetRequiredService<IUserService>();
            var logger = services.GetRequiredService<ILogger<Program>>(); // Get a logger

            Console.WriteLine("--- Create Initial Admin User ---");

            string? displayName;
            do
            {
                Console.Write("Enter Admin Display Name (min 3 chars): ");
                displayName = Console.ReadLine();
            } while (string.IsNullOrWhiteSpace(displayName) || displayName.Length < 3);


            string? password;
            string? confirmPassword;
            do
            {
                Console.Write("Enter Admin Password (min 6 chars): ");
                // Basic masking attempt (may not work on all terminals)
                password = ReadPassword();
                Console.WriteLine(); // New line after password input

                Console.Write("Confirm Admin Password: ");
                confirmPassword = ReadPassword();
                Console.WriteLine(); // New line after password input

                if (string.IsNullOrWhiteSpace(password) || password.Length < 6)
                {
                    Console.WriteLine("Password must be at least 6 characters long.");
                }
                else if (password != confirmPassword)
                {
                    Console.WriteLine("Passwords do not match. Please try again.");
                }
            } while (string.IsNullOrWhiteSpace(password) || password.Length < 6 || password != confirmPassword);


            var adminModel = new UserViewModel
            {
                DisplayName = displayName,
                Password = password,
                ConfirmPassword = confirmPassword, // Needed for validation within AddUserAsync if it checks
                IsAdmin = true
            };

            logger.LogInformation("Attempting to create initial admin user '{DisplayName}'...", adminModel.DisplayName);

            var existingUser = await userService.GetUserByDisplayNameAsync(adminModel.DisplayName);
            if (existingUser != null)
            {
                 Console.ForegroundColor = ConsoleColor.Red;
                 Console.WriteLine($"Error: User with display name '{adminModel.DisplayName}' already exists.");
                 Console.ResetColor();
                 logger.LogWarning("Initial admin creation failed: User '{DisplayName}' already exists.", adminModel.DisplayName);
                 return; // Exit application
            }

            var newUser = await userService.AddUserAsync(adminModel);

            if (newUser != null)
            {
                Console.ForegroundColor = ConsoleColor.Green;
                Console.WriteLine($"Admin user '{newUser.DisplayName}' created successfully with folder '{newUser.FolderName}'.");
                Console.ResetColor();
                logger.LogInformation("Initial admin user '{DisplayName}' created successfully.", newUser.DisplayName);
            }
            else
            {
                 Console.ForegroundColor = ConsoleColor.Red;
                 Console.WriteLine("Error: Failed to create admin user. Check logs for details.");
                 Console.ResetColor();
                 logger.LogError("Initial admin creation failed for '{DisplayName}'.", adminModel.DisplayName);
            }

            return; // Exit application after attempting creation
        }
        // --- End Admin Creation Argument Check ---


        // Configure the HTTP request pipeline (Normal execution if --create-admin not present)
        if (!app.Environment.IsDevelopment())
        {
            app.UseExceptionHandler("/Home/Error");
            // The default HSTS value is 30 days. You may want to change this for production scenarios, see https://aka.ms/aspnetcore-hsts.
            app.UseHsts();
        }

        app.UseHttpsRedirection();
        app.UseStaticFiles(); // Explicitly add UseStaticFiles

        app.UseRouting();

        app.UseAuthentication(); // Add Authentication middleware
        app.UseAuthorization();

        app.MapStaticAssets();
        app.MapControllerRoute(
            name: "default",
            pattern: "{controller=Home}/{action=Index}/{id?}")
            .WithStaticAssets();

        app.Run();
    }

    // Helper to read password without echoing (basic)
    private static string ReadPassword()
    {
        string password = "";
        ConsoleKeyInfo key;
        do
        {
            key = Console.ReadKey(true);
            // Ignore backspace and enter
            if (key.Key != ConsoleKey.Backspace && key.Key != ConsoleKey.Enter)
            {
                password += key.KeyChar;
                Console.Write("*");
            }
            else
            {
                if (key.Key == ConsoleKey.Backspace && password.Length > 0)
                {
                    password = password.Substring(0, (password.Length - 1));
                    Console.Write("\b \b"); // Erase the last '*'
                }
            }
        }
        // Stop reading when Enter is pressed
        while (key.Key != ConsoleKey.Enter);
        return password;
    }
}
