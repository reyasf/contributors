<?php

/*
Plugin Name: Contributors
*/

class contributors {
    
    protected static $_object = null;
    
    public function __construct() {
        if (is_admin()) {
            add_action('admin_menu', array(&$this, 'add_meta_box'));
            add_action('save_post', array(&$this, 'save_meta'));
        } else {
            add_filter( 'the_content', array(&$this, 'load_contributors'), 30);
            wp_enqueue_style("contributors-css", plugins_url('/css/styles.css?ver=1', __FILE__));
        }
    }
    
    /*
     * create the object
     */
    public static function initialize() {
        if (is_null(self::$_object)) {
            self::$_object = new self();
        }
        return self::$_object;
    }
    
    /*
     * Add Meta Box
     * Add the meta box for post page and custom post types
     */
    public function add_meta_box() {
        add_meta_box("contributors", 'Contributors', array(&$this, 'show_contributors'), "post", "normal", "high");
        add_meta_box("contributors", 'Contributors', array(&$this, 'show_contributors'), "page", "normal", "high");
        $post_types = get_post_types(array('public' => true, '_builtin' => false), 'names', 'and'); 
        foreach ($post_types as $post_type ) {
            add_meta_box("contributors", 'Contributors', array(&$this, 'show_contributors'), $post_type, "normal", "high");
        }		
    }
    
    /*
     * List Contributors
     * List the contributors to be added inside the metabox
     */
    public function show_contributors() {
        global $post;
        $meta = $this->get_meta($post->ID);
        $users = get_users();

        print ('<table class="contributors_options"><tr><th style="width: 100px;">Please select contributors:</th></tr>');
        $allowed_roles = array('editor', 'administrator', 'author');
        foreach($users as $user){
            if( array_intersect($allowed_roles, $user->roles ) ) {
                $checked = "";
                if(in_array($user->ID,$meta)) {
                    $checked = "checked";
                }
                print ('<tr><td><input type="checkbox" name="contributors[]" value="'.$user->ID.'" '.$checked.' />'.$user->display_name.'</td></tr>');
            }
        }
        print ('</table>');
    }
    
    /*
     * Get postmeta contributors_list
     */
    public function get_meta($post_id) {
        return get_post_meta($post_id, 'contributors_list', true);
    }
    
    /*
     * Save Meta
     * Save the metabox metadata while saving the post
     * Also check whether the current user has permission to edit the post and post is valid
     */
    public function save_meta($post_id) {
        if (isset($_POST['post_type'])) $post_type = $_POST['post_type'];
        else $_POST['post_type'] = null;
            $post_type_object = get_post_type_object($_POST['post_type']);

        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (!isset($_POST['post_ID']) || $post_id != $_POST['post_ID']) || (!current_user_can($post_type_object->cap->edit_post, $post_id))) {
            return $post_id;
        }
        update_post_meta($post_id, "contributors_list", $_POST["contributors"]);
        return $post_id;
    }
    
    /*
     * Load Contributors
     * Load all the assigned contributors to the post
     * The Gravatar link is used considering the user has a gravatar account
     */
    public function load_contributors() {
        global $post;
        $contributors = $this->get_meta($post->ID);
        $_html = '<div class="contributors-box">';
        $_html .= '<div class="section_title">Contributors</div>';
        foreach($contributors as $contributor){
            $user = get_user_by("ID",$contributor);
            $_html .= '<div class="contributors"><div class="gravatar">'.get_avatar($user->ID,16).'</div><div class="display_name"><a href='.get_avatar_url($user->ID).'>'.$user->display_name.'</a></div></div>';
        }
        $_html .= '</div>';
        return $_html;
    }
}
contributors::initialize();
?>