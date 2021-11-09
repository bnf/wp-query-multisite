<?php
/**
 * WPQueryMultisite class.
 *
 * @package     TimJensen\WPQueryMultisite
 * @author      Tim Jensen <tim@timjensen.us>
 * @license     GNU General Public License 2.0+
 * @link        https://www.timjensen.us
 */

namespace TimJensen\WPQueryMultisite;

/**
 * Class WPQueryMultisite
 *
 * @package TimJensen\WPQueryMultisite
 */
class WPQueryMultisite {

	/**
	 * Static proxy for returning a shared instance of the class.
	 *
	 * @return WPQueryMultisite
	 */
	public static function make() {
		static $self = null;

		if ( null === $self ) {
			$self = new self();
		}

		return $self;
	}

	function init() {
		add_filter('query_vars', array($this, 'query_vars'));
		add_action('pre_get_posts', array($this, 'pre_get_posts'), 100);
		add_filter('posts_clauses', array($this, 'posts_clauses'), 10, 2);
		add_filter('posts_request', array($this, 'posts_request'), 10, 2);
		add_action('the_post', array($this, 'the_post'));
		add_action('loop_end', array($this, 'loop_end'));
	}

	function query_vars($vars) {
		$vars[] = 'multisite';
		$vars[] = 'sites__not_in';
		$vars[] = 'sites__in';
		return $vars;
	}

	function pre_get_posts($query) {
		if($query->get('multisite')) {

			global $wpdb;

			$this->loop_end = false;
			$this->blog_id = get_current_blog_id();

			$site_IDs = $wpdb->get_col( "select blog_id from {$wpdb->blogs}" );

			if ( $query->get('sites__not_in') )
				foreach($site_IDs as $key => $site_ID )
					if (in_array($site_ID, $query->get('sites__not_in')) ) unset($site_IDs[$key]);

			if ( $query->get('sites__in') )
				foreach($site_IDs as $key => $site_ID )
					if ( ! in_array($site_ID, $query->get('sites__in')) )
						unset($site_IDs[$key]);

			$site_IDs = array_values($site_IDs);
			$site_IDs = array_map('intval', $site_IDs);

			$this->sites_to_query = $site_IDs;
		}
	}

	function posts_clauses($clauses, $query) {
		if($query->get('multisite')) {
			global $wpdb;

			// Start new mysql selection to replace wp_posts on posts_request hook
			$this->ms_select = array();

			$root_site_db_prefix = $wpdb->prefix;
			foreach($this->sites_to_query as $site_ID) {

				switch_to_blog($site_ID);

				$ms_select = $clauses['join'] . ' WHERE 1=1 '. $clauses['where'];

				if($clauses['groupby'])
					$ms_select .= ' GROUP BY ' . $clauses['groupby'];

				$ms_select = str_replace($root_site_db_prefix, $wpdb->prefix, $ms_select);
				$ms_select = " SELECT {$wpdb->posts}.*, {$site_ID} as site_ID FROM {$wpdb->posts} {$ms_select} ";

				$this->ms_select[] = $ms_select;

				restore_current_blog();

			}

			// Clear join, where and groupby to populate with parsed ms select on posts_request hook;
			$clauses['join'] = '';
			$clauses['where'] = '';
			$clauses['groupby'] = '';

			// Orderby for tables (not wp_posts)
			$clauses['orderby'] = str_replace($wpdb->posts, 'tables', $clauses['orderby']);

		}
		return $clauses;
	}

	function posts_request($sql, $query) {

		if($query->get('multisite')) {

			global $wpdb;

			// Clean up remanescent WHERE request
			$sql = str_replace('WHERE 1=1', '', $sql);

			// Multisite request
			$sql = str_replace("{$wpdb->posts}.* FROM {$wpdb->posts}", 'tables.* FROM ( ' . implode(" UNION ", $this->ms_select) . ' ) tables', $sql);

		}

		return $sql;
	}

	function the_post($post) {

		if (isset($post->site_ID)) {
			$post->site_ID = (int)$post->site_ID;
		}

		if( isset( $this->loop_end ) && !$this->loop_end && isset($post->site_ID) && get_current_blog_id() !== $post->site_ID) {
			switch_to_blog($post->site_ID);
		}

	}

	function loop_end($query) {
		if($query->get('multisite')) {
			$this->loop_end = true;
			if($GLOBALS['switched'] ?? false) {
				switch_to_blog($this->blog_id);
			}
		}
	}
}
