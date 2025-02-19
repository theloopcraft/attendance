module.exports = {
    apps: [
        {
            name: "laravel-scheduler",
            script: "C:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\php.exe",
            args: "artisan schedule:work",
            cwd: "C:\\laragon\\www\\h-attendance",
            interpreter: "none",
            autorestart: true,
            watch: false
        }
    ]
};
