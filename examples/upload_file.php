<?php
/**
 * This web service demonstrates how to handle uploaded files
 * in form of 'multipart/form-data'.
 *
 * @Route('upload')
 */
class UploadService extends Service {

  /**
   * The upload method. We only accept png images for this demo.
   *
   * Takes an image and returnes the same image. Not really a useful
   * web service, but it demonstrates that files may be upped to a
   * service under the phpREST library.
   *
   * @ContentType('image/png')
   */
  public function post($file) {

    // Check that we've got input
    if ($file !== NULL) {

      // Check file type
      if ($file['type'] == 'image/png') {

        // Return the image uploaded to demonstrate that we got it
        echo file_get_contents($file['tmp_name']);

      } else {
        throw new ServiceException(HttpStatus::BAD_REQUEST,
          'Uploaded file is not a png image');
      }
    } else {
      throw new ServiceException(HttpStatus::BAD_REQUEST,
        'Missing file');
    }
  }

}

// The $server object is instantiated in index.php

// Register our service with the server
$server->addService(new UploadService());
