<?php

namespace Drupal\islandora_attach_media\Commands;

use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drush\Commands\DrushCommands;
// use Drupal\islandora\Flysystem\Fedora;

/**
 * Drush commandfile.
 */
class IslandoraAttachMediaCommands extends DrushCommands {

  /**
   * Creates a media from a file and attaches the media to a node.
   *
   * This utility only creates the File and Media entities in Drupal, it does not
   * touch the files themselves. In other words, if the filesystem schema is
   * 'public://' or 'private://', the file must exist there already; if the schema
   * is 'fedora://', the binary resource must exist in Fedora already. This is a
   * feature, not a deficiency, since Drupal's FileSystem.php's saveData() uses
   * file_put_contents(), which is limited to handling files that are smaller than
   * the PHP's configured memory limits.
   *
   * @param string $nid
   *   The node ID.
   *
   * @param string $source_file_path
   *   The absolute path to the source file, or URL to the file in Fedora.
   *
   * @command islandora_attach_media:attach
   * @usage islandora_attach_media:attach 100 /tmp/image.jpg
   */
  public function attach($nid, $source_file_path) {
    if (!$node = Node::load($nid)) {
      $this->logger()->error(dt('Node @nid not found.', ['@nid' => $nid]));
      exit();
    }
    /*
    // @todo: Ping Fedora URL to confirm the file is there.
    if (!file_exists($source_file_path)) {
      $this->logger()->error(dt('File @source_file_path not found.', ['@source_file_path' => $source_file_path]));
      exit();
    }
     */

    // D9: $real_path = \Drupal\Core\File\FileSystem::realpath($source_file_path);
    $real_path = drupal_realpath($source_file_path);
    // Thows error: $real_path = \Drupal\islandora\Flysystem\Fedora::getExternalUrl($source_file_path);

    $filename = basename($source_file_path);

    $uri = 'public://nocopy.txt';
    // $uri = 'fedora://2020-09/article.pdf';

    // Create a file entity.
    $file = File::create([
      'uri' => $uri,
      'uid' => \Drupal::currentUser()->id(),
      // 'filesize' => use filesize() here for local files, or get hasSize property from Fedora.
      'status' => FILE_STATUS_PERMANENT,
    ]);
/*
    // Copied from https://api.drupal.org/api/drupal/core!modules!file!file.module/function/file_save_data/8.8.x.
    // If we are replacing an existing file re-use its database record.
    // @todo Do not create a new entity in order to update it. See
    //   https://www.drupal.org/node/2241865.
    if ($replace == FILE_EXISTS_REPLACE) {
      $existing_files = entity_load_multiple_by_properties('file', array(
        'uri' => $uri,
      ));
      if (count($existing_files)) {
        $existing = reset($existing_files);
        $file->fid = $existing
          ->id();
        $file
          ->setOriginalId($existing
          ->id());
        $file
          ->setFilename($existing
          ->getFilename());
      }
    }
    elseif ($replace == FILE_EXISTS_RENAME && is_file($destination)) {
      $file
        ->setFilename(drupal_basename($destination));
    }
*/
    $file->save();

    // @todo: 'bundle' is the Media type. We need to be able to support all of them, and even new ones.
    $media = Media::create([
      'bundle' => 'file',
      // @todo: Provide a Drush option for the user ID.
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
