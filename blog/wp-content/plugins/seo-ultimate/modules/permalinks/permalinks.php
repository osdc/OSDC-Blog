<?php
/**
 * Permalink Tweaker Module
 * 
 * @since 5.8
 */

if (class_exists('SU_Module')) {

class SU_Permalinks extends SU_Module {
	
	function get_module_title() { return __('Permalink Tweaker', 'seo-ultimate'); }
	
	function get_parent_module() { return 'misc'; }
	function get_settings_key() { return 'permalinks'; }
	
	function init() {
		if (suwp::permalink_mode()) {
			$nobase_enabled = false;
			$taxonomies = suwp::get_taxonomy_names();
			foreach ($taxonomies as $taxonomy) {
				if ($this->get_setting("nobase_$taxonomy", false)) {
					add_action("created_$taxonomy", array(&$this, 'flush_rewrite_rules'));
					add_action("edited_$taxonomy", array(&$this, 'flush_rewrite_rules'));
					add_action("delete_$taxonomy", array(&$this, 'flush_rewrite_rules'));
					add_filter("{$taxonomy}_rewrite_rules", array(&$this, 'nobase_rewrite_rules'));
					$nobase_enabled = true;
				}
			}
			if ($nobase_enabled) {
				add_filter('term_link', array(&$this, 'nobase_term_link'), 1000, 2);
				add_filter('query_vars', array(&$this, 'nobase_query_vars'));
				add_filter('request', array(&$this, 'nobase_old_base_redirect'));
			}
		}
	}
	
	function admin_page_contents() {
		
		if (!suwp::permalink_mode()) {
			$this->print_message('warning', __('To use the Permalinks Tweaker, you must disable default (query-string) permalinks in your <a href="options-permalink.php">Permalink Settings</a>.', 'seo-ultimate'));
			return;
		}
		
		$this->child_admin_form_start();
		
		$nobase_checkboxes = array();
		$taxonomies = suwp::get_taxonomies();
		foreach ($taxonomies as $taxonomy) {
			
			global $wp_rewrite;
			$before_url = $wp_rewrite->get_extra_permastruct($taxonomy->name);
			$before_url = str_replace("%{$taxonomy->name}%", 'example', $before_url);
			$before_url = home_url( user_trailingslashit($before_url, 'category') );
			
			$after_url = home_url( user_trailingslashit('/example', 'category') );
			
			$nobase_checkboxes['nobase_' . $taxonomy->name] = sprintf(
				  __('%1$s (turn <code>%2$s</code> into <code>%3$s</code>)', 'seo-ultimate')
				, $taxonomy->labels->name
				, $before_url
				, $after_url
			);
		}
		
		$this->checkboxes($nobase_checkboxes, __('Remove the URL bases of...', 'seo-ultimate'));
		$this->child_admin_form_end();
		
		$this->update_rewrite_filters();
		$this->flush_rewrite_rules();
	}
	
	function flush_rewrite_rules() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}
	
	function update_rewrite_filters() {
		if (suwp::permalink_mode()) {
			$taxonomies = suwp::get_taxonomy_names();
			foreach ($taxonomies as $taxonomy) {
				if ($this->get_setting("nobase_$taxonomy", false))
					add_filter("{$taxonomy}_rewrite_rules", array(&$this, 'nobase_rewrite_rules'));
				else
					remove_filter("{$taxonomy}_rewrite_rules", array(&$this, 'nobase_rewrite_rules'));
			}
		}
	}
	
	function nobase_term_link($url, $term_obj) {
		if ($this->get_setting('nobase_' . $term_obj->taxonomy, false))
			return home_url( user_trailingslashit('/' . suwp::get_term_slug($term_obj), 'category') );
		
		return $url;
	}
	
	function nobase_rewrite_rules($rules) {
		$rules=array();
		
		$tax_name = sustr::rtrim_str(current_filter(), '_rewrite_rules');
		$tax_obj = get_taxonomy($tax_name);
		
		$terms = get_terms($tax_name);
		if ($terms && !is_wp_error($terms)) {
			foreach ($terms as $term_obj) {
				$term_slug = suwp::get_term_slug($term_obj);
				
				if ($tax_obj->query_var && is_string($tax_obj->query_var))
					$url_start = "index.php?{$tax_obj->query_var}=";
				else
					$url_start = "index.php?taxonomy={$tax_name}&term=";
				
				$rules['('.$term_slug.')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = $url_start . '$matches[1]&feed=$matches[2]';
				$rules['('.$term_slug.')/page/?([0-9]{1,})/?$'] = $url_start . '$matches[1]&paged=$matches[2]';
				$rules['('.$term_slug.')/?$'] = $url_start . '$matches[1]';
			}
		}
		
		global $wp_rewrite;
		$old_base = $wp_rewrite->get_extra_permastruct($tax_name);
		$old_base = str_replace( "%{$tax_name}%", '(.+)', $old_base );
		$old_base = trim($old_base, '/');
		$rules[$old_base.'$'] = 'index.php?su_term_redirect=$matches[1]';
		
		return $rules;
	}
	
	function nobase_query_vars($query_vars) {
		$query_vars[] = 'su_term_redirect';
		return $query_vars;
	}
	
	function nobase_old_base_redirect($query_vars) {
		if (isset($query_vars['su_term_redirect'])) {
			wp_redirect(home_url(user_trailingslashit($query_vars['su_term_redirect'], 'category')));
			exit;
		}
		return $query_vars;
	}
}

}
?>