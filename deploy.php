<?php

namespace Deployer;

require 'recipe/common.php';

// ---------------------------------------------------------------------------
// Quinnix Software — Deployer 7 configuration
//
// Architecture mirrors the VoxMason site deployment (voxmason-site/deploy.php).
// The deploy_path holds versioned releases; the document_root is rsynced
// from the active release on every deploy and rollback.
//
// Required environment variables (set in shell before running dep):
//
//   QUINNIX_REPOSITORY      Git clone URL of this repository
//   QUINNIX_DEPLOY_PATH     Versioned-release base directory (NOT the doc root)
//                           e.g. /home/quinni5/quinnix-deploy
//   QUINNIX_DOCUMENT_ROOT   Apache document root for quinnix.com
//                           *** CONFIRM before first deploy — do not assume ***
//
// Optional:
//   QUINNIX_HOST            SSH hostname  (default: ehub63.webhostinghub.com)
//   QUINNIX_SSH_PORT        SSH port      (default: 2222)
//   QUINNIX_DEPLOY_BRANCH   Branch        (default: main)
//
// BEFORE FIRST DEPLOY — confirm on the server via SSH:
//   ssh -p 2222 quinni5@YOUR_SERVER_HOST
//   ls -la /home/quinni5/quinnix.com        # confirm doc root path and contents
//   rsync --version && git --version         # confirm server requirements
//
// NEVER deploy until QUINNIX_DOCUMENT_ROOT has been confirmed.
// ---------------------------------------------------------------------------

$hostName     = getenv('QUINNIX_HOST')           ?: 'ehub63.webhostinghub.com';
$sshPort      = (int) (getenv('QUINNIX_SSH_PORT') ?: 2222);
$repository   = getenv('QUINNIX_REPOSITORY')     ?: 'REPOSITORY_NOT_CONFIGURED';
$deployPath   = getenv('QUINNIX_DEPLOY_PATH')    ?: '/PATH/TO/QUINNIX_DEPLOY_ROOT';
$documentRoot = getenv('QUINNIX_DOCUMENT_ROOT')  ?: '';   // no default — must be confirmed
$branch       = getenv('QUINNIX_DEPLOY_BRANCH')  ?: 'main';

// Hosting-managed directories rsync must never delete
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
    ->set('hostname',    $hostName)
    ->set('port',        $sshPort)
    ->set('remote_user', 'quinni5')
    ->set('deploy_path', $deployPath);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

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
    return 'rsync -az --delete ' . implode(' ', $excludes) . " {$src} {$dest}/";
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
// Validation
// ---------------------------------------------------------------------------

desc('Confirm required deployment values are set and safe');
task('deploy:validate_configuration', function (): void {
    $required = [
        'QUINNIX_REPOSITORY'    => getenv('QUINNIX_REPOSITORY'),
        'QUINNIX_DEPLOY_PATH'   => getenv('QUINNIX_DEPLOY_PATH'),
        'QUINNIX_DOCUMENT_ROOT' => getenv('QUINNIX_DOCUMENT_ROOT'),
    ];

    foreach ($required as $name => $value) {
        if ($value === false || trim((string) $value) === '') {
            throw new \RuntimeException("Required variable {$name} is not set.");
        }
    }

    $dp = rtrim((string) getenv('QUINNIX_DEPLOY_PATH'), '/');
    $dr = rtrim((string) getenv('QUINNIX_DOCUMENT_ROOT'), '/');

    if ($dp === '/' || strlen($dp) < 8) {
        throw new \RuntimeException('QUINNIX_DEPLOY_PATH is too broad or missing.');
    }
    if ($dr === '/' || strlen($dr) < 8) {
        throw new \RuntimeException('QUINNIX_DOCUMENT_ROOT is too broad or missing.');
    }
    if ($dp === $dr) {
        throw new \RuntimeException(
            'QUINNIX_DEPLOY_PATH must not equal QUINNIX_DOCUMENT_ROOT. ' .
            'Use a separate sibling directory, e.g. /home/quinni5/quinnix-deploy.'
        );
    }
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
    writeln('Server requirements satisfied (rsync, git)');
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

after('rollback',        'deploy:publish_current');
after('deploy:failed',   'deploy:unlock');
