<?php
/**
 * Fetch API client class.
 * @package WordPress_GitHub_Sync
 */

/**
 * Class WordPress_GitHub_Sync_Fetch_Client
 */
class WordPress_GitHub_Sync_Fetch_Client extends WordPress_GitHub_Sync_Base_Client {

	/**
	 * Compare a commit by sha with master from the GitHub API
	 *
	 * @param string $sha Sha for commit to retrieve.
	 *
	 * @return array[Writing_On_GitHub_File_Info]|WP_Error
	 */
	public function compare( $sha ) {
		// https://api.github.com/repos/lite3/testwpsync/compare/861f87e8851b8debb78db548269d29f8da4d94ac...master
		$endpoint = $this->compare_endpoint();
		$data = $this->call( 'GET', "$endpoint/$sha...master" );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$files = array();
		foreach ($data->files as $file) {
			$file->path = $file->filename;
			$files[] = new Writing_On_GitHub_File_Info($file);
		}

		return $files;
	}

	/**
	 * Calls the content API to get the post's contents and metadata
	 *
	 * Returns Object the response from the API
	 *
	 * @param WordPress_GitHub_Sync_Post $post Post to retrieve remote contents for.
	 *
	 * @return mixed
	 */
	public function remote_contents( $post ) {
		return $this->call( 'GET', $this->content_endpoint( $post->github_path() ) );
	}

	public function exists( $path ) {
		$result = $this->call( 'GET', $this->content_endpoint( $path ) );
		if ( is_wp_error( $result ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Retrieves a tree by sha recursively from the GitHub API
	 *
	 * @param string $sha Commit sha to retrieve tree from.
	 *
	 * @return WordPress_GitHub_Sync_Tree|WP_Error
	 */
	public function tree_recursive( $sha = 'root' ) {

		if ( 'root' === $sha ) {
			$sha = 'master';
		}

		$data = $this->call( 'GET', $this->tree_endpoint() . '/' . $sha . '?recursive=1' );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$files = array();

		foreach ( $data->tree as $index => $thing ) {
			// We need to remove the trees because
			// the recursive tree includes both
			// the subtrees as well the subtrees' blobs.
			if ( 'blob' === $thing->type ) {
				$thing->status = '';
				$files[] = new Writing_On_GitHub_File_Info( $thing );
			}
		}

		return $files;
	}

	/**
	 * Generates blobs for recursive tree blob data.
	 *
	 * @param stdClass[] $blobs Array of tree blob data.
	 *
	 * @return WordPress_GitHub_Sync_Blob[]
	 */
	protected function blobs( array $blobs ) {
		$results = array();

		foreach ( $blobs as $blob ) {
			$obj = $this->blob( $blob );

			if ( ! is_wp_error( $obj ) ) {
				$results[] = $obj;
			}
		}

		return $results;
	}

	/**
	 * Retrieves the blob data for a given sha
	 *
	 * @param stdClass $blob Tree blob data.
	 *
	 * @return WordPress_GitHub_Sync_Blob|WP_Error
	 */
	public function blob( $blob ) {
		if ( $cache = $this->app->cache()->fetch_blob( $blob->sha ) ) {
			return $cache;
		}

		$data = $this->call( 'GET', $this->blob_endpoint() . '/' . $blob->sha );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$data->path = $blob->path;
		$obj = new WordPress_GitHub_Sync_Blob( $data );

		return $this->app->cache()->set_blob( $obj->sha(), $obj );
	}
}
