<?php



function register_my_topten_widget() {	// Widget Kaydet
	register_widget( 'My_Top_Ten_Widget' );
}
add_action( 'widgets_init', 'register_my_topten_widget' );

class My_Top_Ten_Widget extends WP_Widget {

	
	public function __construct() { // Constructor
		parent::__construct('my_top_ten_widget',__( 'Top 10 Post Widget', 'text_domain' ),array('customize_selective_refresh' => true));
	}

	public function form( $instance ) {

		
		$defaults = array(	// Varsayılan Ayarlar
			'title'    => '',
			'checkbox' => '1',
		);
		
		// Varsayılan ayarları parse et
		extract( wp_parse_args( ( array ) $instance, $defaults ) ); ?>

		<?php // Widget Baslik ?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Widget Title', 'text_domain' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<?php // Beğeni Sayısı gösterilsin mi ?>
		<p>
			<input id="<?php echo esc_attr( $this->get_field_id( 'checkbox' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'checkbox' ) ); ?>" type="checkbox" value="1" <?php checked( '1', $checkbox ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'checkbox' ) ); ?>"><?php _e( 'Beğeni Sayısı Göster', 'text_domain' ); ?></label>
		</p>

	<?php }

	// Widget ayarlarını güncelle
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']    = isset( $new_instance['title'] ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['checkbox'] = isset( $new_instance['checkbox'] ) ? 1 : false;
		return $instance;
	}

	// Widget Göstere fonksiyon
	public function widget( $args, $instance ) {

		extract( $args );

		// Widget ayarlarını kontrol et
		$title    = isset( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
		$checkbox = ! empty( $instance['checkbox'] ) ? $instance['checkbox'] : false;

		
		echo $before_widget; // WordPress core before_widget hook (always include)

		echo '<div class="widget-text wp_widget_plugin_box">';

			// Display widget title if defined
			if ( $title ) {
				echo $before_title . $title . $after_title;
			}


			global $wpdb;
			$table_name = $wpdb->prefix . "like_counter";
			
			//En çok beğeni alan 10 post un id ve beğeni sayılarını çek
			$result = $wpdb->get_results("SELECT post_id, count(*) AS count
											FROM $table_name
											GROUP BY post_id
											ORDER BY count(*) DESC
											LIMIT 10"); 

			$postsay = 0;
			echo "<table>"; 
			echo "<tr>"; 
			echo "<th>Post</th>"; 
			if ($checkbox) {echo "<th>Like</th>"; }
			echo "</tr>"; 
			foreach ($result as $details) {
					$postsay++;
					echo "<tr>"; 
						echo "<td>" . getPostNameWithId($details->post_id) . "</td>"; 
						if ($checkbox) { echo "<td>" . $details->count . "</td>"; }
					echo "</tr>"; 
			}
			echo "</table>"; 

			if($postsay == 0){
				echo "No Liked Post Yet :(";
			}


		echo '</div>';

		// WordPress core after_widget hook (always include )
		echo $after_widget;

	}


}