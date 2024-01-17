<?php

namespace classes;


/**
 * Class AddFeaturedImages
 *
 * This class is responsible for adding an extra featured image for the post.
 */
class AddFeaturedImages {

    protected string $post_type;
    protected string $metabox_title;
    protected string $identifier;

    public function __construct($post_type, $metabox_title, $identifier) {
        $this->post_type = $post_type;
        $this->metabox_title = $metabox_title;
        $this->identifier = $identifier;

        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save'));
    }

    public function add_meta_box(): void {
        add_meta_box(
            $this->identifier,
            $this->metabox_title,
            array($this, 'render_meta_box_content'),
            $this->post_type,
            'side',
            'low'
        );
    }

    public function render_meta_box_content($post): void {

        wp_nonce_field($this->identifier, $this->identifier . '_nonce');
        $thumbnail_id = get_post_meta($post->ID, $this->identifier . '_id', true);

        // Inserisci l'immagine all'interno del div dell'anteprima.
        echo '<div id="' . $this->identifier . '_preview" style="margin-bottom: 10px;">';
        if($thumbnail_id) {
            echo wp_get_attachment_image($thumbnail_id, 'thumbnail');
        }
        echo '</div>';

        echo '<div class="extra_featured_buttons_container">';
        echo '<input type="hidden" id="' . $this->identifier . '_id" name="' . $this->identifier . '_id" value="'. ($thumbnail_id ? $thumbnail_id : '').'">';
        echo '<button type="button" id="' . $this->identifier . '_button" class="upload_image_button button">Set extra featured image</button>';
        echo '<button type="button" id="remove_' . $this->identifier . '_button" class="remove_image_button button">Remove extra featured image</button>';
        echo '</div>';
        // Modifica lo script JavaScript per non duplicare l'immagine.
        echo "<script>
        document.addEventListener('DOMContentLoaded', () => {
            let frame;
            const addButton = document.getElementById('" . $this->identifier . "_button');
            const removeButton = document.getElementById('remove_" . $this->identifier . "_button');
            const preview = document.getElementById('" . $this->identifier . "_preview');
            const imageIdInput = document.getElementById('" . $this->identifier . "_id');

            addButton.addEventListener('click', (event) => {
                event.preventDefault();
                if (frame) {
                    frame.open();
                    return;
                }
                frame = wp.media({
                    title: 'Select or Upload Extra Featured Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });

                frame.on('select', () => {
                    const attachment = frame.state().get('selection').first().toJSON();
                    if (attachment && attachment.id) {
                        imageIdInput.value = attachment.id;
                        if (preview.children.length > 0) { 
                            preview.children[0].src = attachment.url;  
                        } else {
                            preview.innerHTML = '<img src=\"' + attachment.url + '\" alt=\"\"/>';
                        }
                    }
                });

                frame.open();
            });

            removeButton.addEventListener('click', (event) => {
                event.preventDefault();
                imageIdInput.value = '';
                if (preview.children.length > 0) { 
                    preview.removeChild(preview.children[0]); 
                }
            });
        });
    </script>";

        echo "<style> 
                .postbox img {width: 100%!important;object-fit: contain;}
                .extra_featured_buttons_container {text-align: center;}
                .extra_featured_buttons_container .button {width: 100%; margin-bottom: 10px}
            </style>";
    }

    public function save($post_id): void {
        if ( ! isset( $_POST[$this->identifier . '_nonce'] ) || ! wp_verify_nonce( $_POST[$this->identifier . '_nonce'], $this->identifier ) ) {
            return;
        }
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can('edit_post', $post_id) ) {
            return;
        }
        if ( isset($_POST[$this->identifier . '_id']) ) {
            update_post_meta($post_id, $this->identifier . '_id', $_POST[$this->identifier . '_id']);
        } else {
            delete_post_meta($post_id, $this->identifier . '_id');
        }
    }

}