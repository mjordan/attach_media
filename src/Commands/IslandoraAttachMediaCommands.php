<?php

namespace Drupal\islandora_attach_media\Commands;

use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drush\Commands\DrushCommands;

/**
 * Drush commandfile.
 */
class IslandoraAttachMediaCommands extends DrushCommands {

  /**
   * Attaches the file to the node.
   *
   * @param string $nid
   *   The node ID.
   *
   * @param string $source_file_path
   *   The absolute path to the source file.
   *
   * @command islandora_attach_media:attach
   * @usage islandora_attach_media:attach 100 /tmp/image.jpg
   */
  public function attach($nid, $source_file_path) {
    if (!$node = Node::load($nid)) {
      $this->logger()->error(dt('Node @nid not found.', ['@nid' => $nid]));
      exit();
    }
    if (!file_exists($source_file_path)) {
      $this->logger()->error(dt('File @source_file_path not found.', ['@source_file_path' => $source_file_path]));
      exit();
    }

    // @todo: Figure out best way to read large files. But...to quote Bender, "We're boned."
    // core/lib/Drupal/Core/File/FileSystem.php's saveData() uses file_put_contents(),
    // which is, along with file_get_contents(), what we need to avoid because they read the
    // entire file into memory. saveData() is called regardless of whether the destination
    // file system is local or Fedora.
    
    $data = file_get_contents($source_file_path);
    $filename = basename($source_file_path);
    $file = file_save_data($data, 'public://' . basename($filename), FILE_EXISTS_REPLACE);
    // $file = file_save_data($data, 'fedora://' . basename($filename), FILE_EXISTS_REPLACE);

    // @todo: 'bundle' is the Media type. We need to be able to support all of them, and even new ones.
    $media = Media::create([
      'bundle' => 'file',
      // 'uid' => \Drupal::currentUser()->id(),
      'uid' => 1,
      // @todo: The field varies by Media type.
      'field_media_file' => [
        'target_id' => $file->id(),
      ],
      'field_media_of' => [
        'target_id' => $nid,
      ],
    ]);

    if ($media->setName($filename)->setPublished(TRUE)->save()) {
      $this->logger()->notice(dt('File @source_file_path attached to node @nid.', ['@source_file_path' => $source_file_path, '@nid' => $nid]));
    }
    else {
      $this->logger()->error(dt('File @source_file_path not attached to node @nid.', ['@source_file_path' => $source_file_path, '@nid' => $nid]));
    }
  }

}
