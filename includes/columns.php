<?php
if(!defined('ABSPATH')) exit;

new Post_Views_Counter_Columns();

class Post_Views_Counter_Columns
{
	public function __construct()
	{
		// actions
		add_action('current_screen', array(&$this, 'register_new_column'));
	}


	/**
	 * 
	*/
	public function register_new_column()
	{
		$screen = get_current_screen();

		if(Post_Views_Counter()->get_attribute('options', 'general', 'post_views_column') && ($screen->base == 'edit' && in_array($screen->post_type, Post_Views_Counter()->get_attribute('options', 'general', 'post_types_count'))))
		{
			foreach(Post_Views_Counter()->get_attribute('options', 'general', 'post_types_count') as $post_type)
			{
				if($post_type === 'page' && $screen->post_type === 'page')
				{
					// actions
					add_action('manage_pages_custom_column', array(&$this, 'add_new_column_content'), 10, 2);

					// filters
					add_filter('manage_pages_columns', array(&$this, 'add_new_column'));
					add_filter('manage_edit-page_sortable_columns', array(&$this, 'register_sortable_custom_column'));
				}
				elseif($post_type === 'post' && $screen->post_type === 'post')
				{
					// actions
					add_action('manage_posts_custom_column', array(&$this, 'add_new_column_content'), 10, 2);

					// filters
					add_filter('manage_posts_columns', array(&$this, 'add_new_column'));
					add_filter('manage_edit-post_sortable_columns', array(&$this, 'register_sortable_custom_column'));
				}
				elseif($screen->post_type === $post_type)
				{
					// actions
					add_action('manage_'.$post_type.'_posts_custom_column', array(&$this, 'add_new_column_content'), 10, 2);

					// filters
					add_filter('manage_'.$post_type.'_posts_columns', array(&$this, 'add_new_column'));
					add_filter('manage_edit-'.$post_type.'_sortable_columns', array(&$this, 'register_sortable_custom_column'));
				}
			}
		}
	}


	/**
	 * Registers sortable column
	*/
	public function register_sortable_custom_column($columns)
	{
		// adds new sortable column
		$columns['post_views'] = 'post_views';

		return $columns;
	}


	/**
	 * Adds new content to specific column
	*/
	public function add_new_column_content($column_name, $id)
	{
		if($column_name === 'post_views')
		{
			global $wpdb;

			// gets post views
			$views = $wpdb->get_var(
				$wpdb->prepare("
					SELECT count
					FROM ".$wpdb->prefix."post_views
					WHERE id = %d AND type = 4",
					$id
				)
			);

			echo (int)$views;
		}
	}


	public function add_new_column($columns)
	{
		$offset = 0;

		if(isset($columns['date']))
			$offset++;

		if(isset($columns['comments']))
			$offset++;

		if($offset > 0)
		{
			$date = array_slice($columns, -$offset, $offset, true);

			foreach($date as $column => $name)
			{
				unset($columns[$column]);
			}

			$columns['post_views'] = __('Post Views', 'post-views-counter');

			foreach($date as $column => $name)
			{
				$columns[$column] = $name;
			}
		}
		else
			$columns['post_views'] = __('Post Views', 'post-views-counter');

		return $columns;
	}
}
?>