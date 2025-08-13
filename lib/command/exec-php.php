<?php

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package project
 *
 * @internal
 */
class rex_project_command_run_snippet extends rex_console_command
{
    protected function configure(): void
    {
        $this
            ->setDescription('Executes a PHP snippet in the REDAXO context (DEVELOPMENT ONLY)')
            ->addArgument('code', InputArgument::REQUIRED, 'The PHP code snippet to execute')
            ->setHelp('This command allows executing arbitrary PHP code and should only be used in development environments.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getStyle($input, $output);

        // Security checks
        if (!$this->isSecure()) {
            $io->error('This command is only available in development environments for security reasons.');
            $io->note('To enable this command, set one of the following:');
            $io->listing([
                'Environment variable: REDAXO_DEV_MODE=1',
                'Create file: var/dev_mode',
                'Run from localhost with CLI SAPI'
            ]);
            return 1;
        }

        $code = $input->getArgument('code');

        // Basic code validation
        if ($this->containsDangerousCode($code)) {
            $io->error('The provided code contains potentially dangerous operations.');
            return 1;
        }

        try {
            // Enable output buffering to capture any output
            ob_start();

            // Execute the PHP snippet in the REDAXO context
            $result = eval($code);

            // Get any output that was generated
            $outputContent = ob_get_clean();

            if (!empty($outputContent)) {
                $io->writeln($outputContent);
            }

            if (null !== $result) {
                $io->writeln('Result: ' . var_export($result, true));
            }

            $io->success('Snippet executed successfully.');
            return 0;
        } catch (\Throwable $e) {
            // Clean output buffer in case of error
            ob_end_clean();

            $io->error('Error executing snippet: ' . $e->getMessage());
            $io->writeln('File: ' . $e->getFile() . ':' . $e->getLine());
            $io->writeln('Stack trace:');
            $io->writeln($e->getTraceAsString());

            return 1;
        }
    }

    /**
     * Check if the command is running in a secure environment
     */
    private function isSecure(): bool
    {

        // Check environment variable
        if (getenv('REDAXO_DEV_MODE') === '1') {
            return true;
        }

        // Check .env.local file for mode variable
        $envFile = rex_path::base('.env.local');
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            if (preg_match('/^mode\s*=\s*dev/m', $envContent)) {
                return true;
            }
        }

        // Check if running via CLI and from localhost
        if (
            php_sapi_name() === 'cli' &&
            (gethostname() === 'localhost' || ($_SERVER['SERVER_NAME'] ?? '') === 'localhost')
        ) {
            return true;
        }

        // Check if ydeploy addon is available and check deployment status
        if (rex_addon::get('ydeploy')->isAvailable()) {
            $ydeploy = rex_ydeploy::factory();
            // If not deployed, allow (development environment)
            if (!$ydeploy->isDeployed()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if code contains potentially dangerous operations
     */
    private function containsDangerousCode(string $code): bool
    {
        $dangerousPatterns = [
            '/\bexec\s*\(/',
            '/\bsystem\s*\(/',
            '/\bshell_exec\s*\(/',
            '/\bpassthru\s*\(/',
            '/\bpopen\s*\(/',
            '/\bproc_open\s*\(/',
            '/\bfile_get_contents\s*\(\s*["\']https?:\/\//',
            '/\bcurl_exec\s*\(/',
            '/\bunlink\s*\(/',
            '/\brmdir\s*\(/',
            '/\bmkdir\s*\(/',
            '/\bfile_put_contents\s*\(/',
            '/\bfopen\s*\(.*["\']w/',
            '/\beval\s*\(/',
            '/\binclude\s*\(/',
            '/\brequire\s*\(/',
            '/\b__DIR__/',
            '/\b__FILE__/',
            '/\$_SERVER/',
            '/\$_ENV/',
            '/\$_GET/',
            '/\$_POST/',
            '/\$_COOKIE/',
            '/\$_SESSION/',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $code)) {
                return true;
            }
        }

        return false;
    }
}
