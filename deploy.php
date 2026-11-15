<?php

namespace Deployer;

require 'recipe/common.php';

// ---------------------------------------------------------------------------
// Quinnix Software — Deployer 6 configuration
//
// Tested with Deployer 6.8.0. Architecture mirrors the VoxMason site:
// versioned releases in deploy_path, rsynced to document_root on each
// deploy and rollback.
//
// All deployment values have confirmed defaults and do not require
// environment variables to be set, but can be overridden by them.
//
//   QUINNIX_REPOSITORY      default: git@github.com:rugsrme/quinnix-site.git
//   QUINNIX_DEPLOY_PATH     default: /home/quinni5/quinnix_deploy
//   QUINNIX_DOCUMENT_ROOT   default: /home/quinni5/public_html   (confirmed)
//   QUINNIX_HOST            default: ehub63.webhostinghub.com
//   QUINNIX_SSH_PORT        default: 2222
//   QUINNIX_DEPLOY_BRANCH   default: main
//
// Deploy command:   dep deploy production
// Rollback command: dep rollback production
// ---------------------------------------------------------------------------

$hostName     = getenv('QUINNIX_HOST')           ?: 'ehub63.webhostinghub.com';
$sshPort      = (int) (getenv('QUINNIX_SSH_PORT') ?: 2222);
$repository   = getenv('QUINNIX_REPOSITORY')     ?: 'git@github.com:rugsrme/quinnix-site.git';
$deployPath   = getenv('QUINNIX_DEPLOY_PATH')    ?: '/home/quinni5/quinnix_deploy';
$documentRoot = getenv('QUINNIX_DOCUMENT_ROOT')  ?: '/home/quinni5/public_html';
$branch       = getenv('QUINNIX_DEPLOY_BRANCH')  ?: 'main';

// /home/quinni5/public_html is the primary domain root and may contain
// unrelated directories (subdomains, cPanel files). List any directory
// that must never be deleted. Extend this list after inspecting the server.
$protectedDirs = ['.well-known', 'cgi-bin'];

set('application',           'quinnix-site');
set('repository',            $repository);
set('branch',                $branch);
set('keep_releases',         5);
set('shared_files',          []);
set('shared_dirs',           []);
set('writable_dirs',         []);
set('allow_anonymous_stats', false);
set('git_tty',               false);
set('document_root',         $documentRoot);
set('protected_dirs',        $protectedDirs);

host($hostName)
    ->stage('production')
    ->set('hostname',    $hostName)
    ->set('port',        $sshPort)
    ->set('remote_user', 'quinni5')
    ->set('deploy_path', $deployPath);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

// Non-destructive rsync: --delete is intentionally omitted.
//
// /home/quinni5/public_html is the primary domain document root. It may
// contain subdomain directories, cPanel-managed files, and other content
// not belonging to this site. Deleting those files would break unrelated
// services on the same account.
//
// Once the contents of public_html have been inspected via SSH and all
// directories that must be preserved are confirmed, --delete can be re-added
// here (with those directories added to $protectedDirs above).
function qxBuildRsync(string $src, string $dest): string
{
    $excludes = [
        '--exclude=.git',
        '--exclude=deploy.php',
        '--exclude=config.php',
        '--exclude=config.sample.php',
        '--exclude=deployer.phar',
        '--exclude=*.md',
    ];
    foreach (get('protected_dirs') as $dir) {
        $dir        = trim($dir, '/') . '/';
        $excludes[] = "--exclude={$dir}";
    }
    return 'rsync -az ' . implode(' ', $excludes) . " {$src} {$dest}/";
}

function qxAssertSource(string $src): void
{
    $result = run("test -f {$src}index.html && test -d {$src}assets && echo OK || echo MISSING");
    if (trim($result) !== 'OK') {
        throw new \RuntimeException(
            "Source {$src} is missing index.html or assets/ — aborting to protect the document root."
        );
    }
}

// ---------------------------------------------------------------------------
// Validation — uses Deployer's resolved config values, not raw env vars,
// so defaults baked into this file are validated correctly even when no
// environment variables are set.
// ---------------------------------------------------------------------------

desc('Confirm deployment configuration is set and safe');
task('deploy:validate_configuration', function (): void {
    $repo = (string) get('repository');
    $dp   = rtrim((string) get('deploy_path'), '/');
    $dr   = rtrim((string) get('document_root'), '/');

    if ($repo === '') {
        throw new \RuntimeException('repository is not configured.');
    }
    if ($dp === '') {
        throw new \RuntimeException('deploy_path is not configured.');
    }
    if ($dr === '') {
        throw new \RuntimeException('document_root is not configured.');
    }
    if ($dp === '/' || strlen($dp) < 8) {
        throw new \RuntimeException("deploy_path '{$dp}' is too broad or missing.");
    }
    if ($dr === '/' || strlen($dr) < 8) {
        throw new \RuntimeException("document_root '{$dr}' is too broad or missing.");
    }
    if ($dp === $dr) {
        throw new \RuntimeException(
            "deploy_path and document_root must not be the same path.\n" .
            "  deploy_path:   {$dp}\n" .
            "  document_root: {$dr}"
        );
    }
    // Warn if paths are nested inside each other (PHP 7 compatible)
    if (strpos($dr . '/', $dp . '/') === 0 || strpos($dp . '/', $dr . '/') === 0) {
        writeln('<comment>Warning: deploy_path and document_root appear to be nested. Verify they are correct.</comment>');
    }

    writeln("  repository:    {$repo}");
    writeln("  deploy_path:   {$dp}");
    writeln("  document_root: {$dr}");
    writeln("  branch:        " . get('branch'));
});

// ---------------------------------------------------------------------------
// Server requirements
// ---------------------------------------------------------------------------

desc('Verify rsync and git are available on the server');
task('deploy:check_requirements', function (): void {
    $rsync = run('which rsync 2>/dev/null || true');
    if (trim($rsync) === '') {
        throw new \RuntimeException('rsync not found on the server.');
    }
    $git = run('which git 2>/dev/null || true');
    if (trim($git) === '') {
        throw new \RuntimeException('git not found on the server.');
    }
    writeln('  rsync: ' . trim($rsync));
    writeln('  git:   ' . trim($git));
});

// ---------------------------------------------------------------------------
// Publish: sync release into the Apache document root
// ---------------------------------------------------------------------------

desc('Publish the just-deployed release to the Apache document root');
task('deploy:publish', function (): void {
    $documentRoot = get('document_root');
    $src          = '{{release_path}}/';

    run("test -d {$documentRoot} || { echo 'Document root {$documentRoot} not found — aborting.'; exit 1; }");
    qxAssertSource($src);
    run(qxBuildRsync($src, $documentRoot));
    writeln("Published release {{release_name}} to {$documentRoot}");
});

desc('Publish the currently symlinked release to the Apache document root');
task('deploy:publish_current', function (): void {
    $documentRoot = get('document_root');
    $currentBase  = '{{deploy_path}}/current';
    $src          = "{$currentBase}/";

    run("test -L {$currentBase} || test -d {$currentBase} || { echo 'No current release at {$currentBase} — aborting.'; exit 1; }");
    run("test -d {$documentRoot} || { echo 'Document root {$documentRoot} not found — aborting.'; exit 1; }");
    qxAssertSource($src);
    run(qxBuildRsync($src, $documentRoot));

    $active = run("readlink -f {$currentBase}");
    writeln("Published current release ({$active}) to {$documentRoot}");
});

// ---------------------------------------------------------------------------
// Deployment sequence
// ---------------------------------------------------------------------------

desc('Deploy the Quinnix static website');
task('deploy', [
    'deploy:validate_configuration',
    'deploy:check_requirements',
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:symlink',
    'deploy:publish',
    'deploy:unlock',
]);

after('rollback',      'deploy:publish_current');
after('deploy:failed', 'deploy:unlock');
