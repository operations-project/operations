<?php

namespace Operations\Composer\Plugin\GitSplit;

/**
 * Splitter offers a command to split a git repository into multiple sub repositories using the splitsh script.
 *
 * @author Jon Pugh <jon@thinkdrop.net>
 */
class Splitter {

  const SPLITSH_NAME = 'splitsh-lite';
  const SPLITSH_URL = 'https://github.com/splitsh/lite/releases/download/v1.0.1/lite_linux_amd64.tar.gz';
  const BIN_FILES = array(
    'splitsh-lite' => 'https://github.com/splitsh/lite/releases/download/v1.0.1/lite_linux_amd64.tar.gz',
  );

  /**
   * Install splitsh-lite script.
   */
  static function install($bin_dir = 'bin') {

      $name = self::SPLITSH_NAME;
      $url = self::SPLITSH_URL;

      // @TODO: Load BIN path from composer project bin path.
      $bin_path = "{$bin_dir}/{$name}";
      if (file_exists($bin_path)) {
        echo "- $name already installed at $bin_path \n";
        return;
      }

      if (strpos($url, 'tar.gz') !== FALSE) {
        $filename = sys_get_temp_dir() . "/$name";
        $filename_tar = "$filename.tar";
        $filename_tar_gz = "$filename_tar.gz";

        echo "- Downloading to $filename_tar_gz \n";
        copy($url, $filename_tar_gz);

        passthru("tar zxf $filename_tar_gz");
        rename("./" . $name, $bin_path);
      }
      else {
        copy($url, $bin_path);
      }

      chmod($bin_path, 0755);
      echo "- Installed $url to $bin_path \n";
    }

  /**
   * Run the splitsh script on each repo.
   */
  static function splitRepos($repos, $show_progress = false, $bin_dir = './bin') {

    // Extracts the currently checked out branch name.
    // In GitHub Actions, this is the branch created in the step "Create a branch for the splitsh"
    $branch_name = trim(shell_exec('git symbolic-ref --quiet --short HEAD 2> /dev/null'));
    $tag_name = trim(shell_exec('git describe --tags --exact-match 2> /dev/null'));

    echo "Current branch: $branch_name";
    echo "Current tag: $tag_name";

    // If the Actions run was triggered by a push, the branch will be named "heads/refs/tags/TAG".
    $is_tag = !empty($tag_name);

    // If is a tag, current_ref contains the string "refs/tags" already.
    if ($is_tag) {
      $bare_tag = $tag_name;
      $target_ref = "refs/tags/$tag_name";
    }
    else {
      $target_ref = "refs/heads/$branch_name";
    }

    foreach ($repos as $folder => $remote) {
      echo "\n\n- Splitting $folder for git reference $branch_name $tag_name to $remote ... \n";


      // Use a different local target branch so we dont break local installs by reassigning the current branch to the new commit.
      $target = "refs/splits/{$folder}-split";

      // Split the commits into a different branch.
      // @TODO: When this becomes a composer plugin, pass -v to --progress.
      $progress = $show_progress? '--progress': '';

      $relative_path = "{$bin_dir}/splitsh-lite";
      $command = realpath($relative_path);
      if (!file_exists($command)) {
        throw new \Exception("The script splitsh-lite was not found in {$relative_path}.");

      }
      elseif (!is_executable($command)) {
        throw new \Exception("The script splitsh-lite file is not executable: {$relative_path}");
      }

      if (self::exec("{$command} {$progress} --prefix={$folder}/ --target=$target") != 0) {
        exit(1);
      }

      // Push the current_ref to the remote.
      if (self::exec("git push --force $remote $target:$target_ref") != 0) {
        exit(1);
      }
    }
  }

  /**
   * Print the command then run it.
   * @param $command
   *
   * @return mixed
   */
  static function exec($command) {
    echo "> $command \n";
    passthru($command, $exit);
    return $exit;
  }
}
