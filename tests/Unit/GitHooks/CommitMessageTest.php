<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * @param  array<string, string|false>  $environment
 */
function runCommitMessageHook(string $message, array $environment = [], ?string $directory = null): Process
{
    $file = sys_get_temp_dir().'/commit-msg-'.bin2hex(random_bytes(8));
    file_put_contents($file, $message);

    $process = new Process(
        [dirname(__DIR__, 3).'/.vite-hooks/commit-msg', $file],
        $directory ?? dirname(__DIR__, 3),
        ['CI' => false, ...$environment],
    );
    $process->run();

    unlink($file);

    return $process;
}

it('accepts a message that follows the convention', function (string $message): void {
    expect(runCommitMessageHook($message)->getExitCode())->toBe(0);
})->with([
    'a module scope' => "feat(foundation): add the git hooks\n",
    'the deps scope' => "build(deps): bump vite-plus to 1.0.1\n",
    'the docs scope' => "docs(docs): explain how to run the browser tests\n",
    'a body' => "fix(foundation): keep the key of a missing line\n\nLaravel returns the key.\n",
    'a header of 72 characters' => 'refactor(foundation): '.str_repeat('a', 50)."\n",
    'comment lines' => "# Please enter the commit message.\nchore(foundation): tidy the seeders\n",
    'a fixup commit' => "fixup! feat(foundation): add the git hooks\n",
    'a squash commit' => "squash! feat(foundation): add the git hooks\n",
]);

it('rejects a message that breaks the convention', function (string $message): void {
    $process = runCommitMessageHook($message);

    expect($process->getExitCode())->toBe(1)
        ->and($process->getErrorOutput())->not->toBe('');
})->with([
    'no type' => "Update stuff.\n",
    'an unknown type' => "feature(foundation): add the git hooks\n",
    'no scope' => "feat: add the git hooks\n",
    'an unknown scope' => "feat(billing): add the git hooks\n",
    'an uppercase subject' => "feat(foundation): Add the git hooks\n",
    'an uppercase word' => "feat(foundation): add the Git hooks\n",
    'a trailing period' => "feat(foundation): add the git hooks.\n",
    'a missing space' => "feat(foundation):add the git hooks\n",
    'an empty subject' => "feat(foundation): \n",
    'a header of 73 characters' => 'refactor(foundation): '.str_repeat('a', 51)."\n",
    'an empty message' => '',
    'a merge message outside a merge' => "Merge branch 'feature'\n",
]);

it('rejects fixup and squash commits in CI', function (string $message): void {
    expect(runCommitMessageHook($message, ['CI' => 'true'])->getExitCode())->toBe(1);
})->with([
    "fixup! feat(foundation): add the git hooks\n",
    "squash! feat(foundation): add the git hooks\n",
]);

it('accepts the message Git gives a merge', function (): void {
    $repository = sys_get_temp_dir().'/commit-msg-merge-'.bin2hex(random_bytes(8));
    mkdir($repository);
    new Process(['git', 'init', '--quiet'], $repository)->mustRun();
    file_put_contents($repository.'/.git/MERGE_HEAD', str_repeat('a', 40)."\n");

    $process = runCommitMessageHook("Merge branch 'feature'\n", directory: $repository);

    new Filesystem()->deleteDirectory($repository);

    expect($process->getExitCode())->toBe(0);
});

it('accepts a module whose only text is interface text', function (): void {
    $project = sys_get_temp_dir().'/commit-msg-project-'.bin2hex(random_bytes(8));
    mkdir($project.'/.vite-hooks', recursive: true);
    mkdir($project.'/lang/en', recursive: true);
    copy(dirname(__DIR__, 3).'/.vite-hooks/commit-msg', $project.'/.vite-hooks/commit-msg');
    chmod($project.'/.vite-hooks/commit-msg', 0755);
    file_put_contents($project.'/lang/en.json', "{\n    \"billing.invoices.title\": \"Invoices\"\n}\n");

    $message = sys_get_temp_dir().'/commit-msg-'.bin2hex(random_bytes(8));
    file_put_contents($message, "feat(billing): list the invoices\n");

    $process = new Process([$project.'/.vite-hooks/commit-msg', $message], $project, ['CI' => false]);
    $process->run();

    unlink($message);
    new Filesystem()->deleteDirectory($project);

    expect($process->getExitCode())->toBe(0);
});
