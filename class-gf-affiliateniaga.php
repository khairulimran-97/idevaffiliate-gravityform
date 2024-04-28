<?php

GFForms::include_addon_framework();

class GFAffiliateNiaga extends GFAddOn {

	protected $_version = GF_AFFILIATENIAGA_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'affiliateniaga';
	protected $_title = 'Gravity Forms Lead AffiliateNiaga';
	protected $_short_title = 'Lead AffiliateNiaga';

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFAffiliateNiaga
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFAffiliateNiaga();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();
		add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );
	}

    // # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

	/**
	 * Configures the settings which should be rendered on the Form Settings > Simple Add-On tab.
	 *
	 * @return array
	 */
    public function form_settings_fields( $form ) {

        $form_id = $form["id"];

        $form = GFAPI::get_form($form_id);

        // Find keys in $form array that start with "gf_whatsapp_number"
        $whatsapp_numbers_keys = preg_grep('/^gf_whatsapp_number/', array_keys($form));

        $whatsapp_numbers_names = array();

        foreach ($whatsapp_numbers_keys as $key) {
            $whatsapp_numbers_names[] = $form[$key];
        }

        $fields = array(
            array(
                'title'  => esc_html__( 'Lead Information for AffiliateNiaga', 'affiliateniaga' ),
                'fields' => array(
                    array(
                        'name'      => 'contactStandardFields',
                        'label'     => esc_html__( 'Lead Information From Portal AfffiliateNiaga ', 'affiliateniaga' ),
                        'type'      => 'field_map',
                        'field_map' => $this->standard_fields_for_feed_mapping(),
                    ),
                ),
            ),
        );

        // Check if the CFWhatsapp_Addon class exists
        if ( class_exists( 'CFWhatsapp_Addon' ) ) {

            $whatsapp_fields = array();

            // Add another field to $whatsapp_fields array
            $whatsapp_fields[] = array(
                'name'     => 'affiliate_field',
                'label'    => esc_html__( 'Admin Phone Number Field', 'affiliateniaga' ),
                'type'     => 'field_select',
                'required' => true,
                'tooltip'  => '<h6>' . esc_html__( 'Admin Phone Number Field', 'affiliateniaga' ) . '</h6>' . esc_html__( 'Select which Gravity Form field will be used as number of admin phone .', 'affiliateniaga' ),
            );

            // Loop through each WhatsApp number name
            foreach ($whatsapp_numbers_names as $name) {
                // Check if the name is not empty
                if (!empty($name)) {
                    // Create a field for each WhatsApp number
                    $whatsapp_fields[] = array(
                        'type'          => 'select',
                        'name'          => 'affiliate_' . sanitize_title( $name ),
                        'label'         => 'Affiliate Name for Admin No - '.$name, // Use WhatsApp number as the label
                        'required'      => false,
                        'class'         => 'medium',

                        'tooltip'       => esc_html__( 'Get Affiliate ID from portal AffiliateNiaga dashboard', 'affiliateniaga' ),
                        'choices' => $this->affiliate_id()
                    );
                }
            }

            // Add the WhatsApp fields to the main fields array
            $fields[] = array(
                'title'  => esc_html__( 'Affiliate ID x Whatsapp Connect', 'affiliateniaga' ),
                'fields' => $whatsapp_fields,
            );

        }

        return $fields;
    }

    public function affiliate_id() {
        // API endpoint URL
        $api_url = 'https://portal.affiliateniaga.com/API/scripts/get_affiliates.php?idev_secret=581117986';

        // Fetch data from the API
        $response = file_get_contents($api_url);

        // Check if the response is valid JSON
        if ($response === false) {
            return array(); // Return an empty array if unable to fetch data
        }

        // Decode JSON response
        $data = json_decode($response, true);

        // Check if JSON decoding was successful and 'data' key exists
        if ($data && isset($data['data'])) {
            // Extract IDs from the 'data' array
            $ids = array();
            foreach ($data['data'] as $affiliate) {
                if (isset($affiliate['id'])) {
                    $username = ucfirst($affiliate['username']);
                    $ids[] = array(
                        'label' => $username,
                        'value' => $affiliate['id']
                    );
                }
            }

            return $ids; // Return array of IDs
        } else {
            return array(); // Return an empty array if 'data' key is not found
        }
    }


    public function standard_fields_for_feed_mapping() {
		return array(

			array(
				'name'          => 'full_name',
				'label'         => esc_html__( 'Lead Full Name', 'affiliateniaga' ),
				'required'      => true,
				'field_type'    => array( 'name', 'text', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'name' ),
			),
			array(
				'name'          => 'email_address',
				'label'         => esc_html__( 'Lead Email Address', 'affiliateniaga' ),
				'required'      => true,
				'field_type'    => array( 'email', 'hidden' ),
				'default_value' => $this->get_first_field_by_type( 'email' ),
			),
			array(
				'name'          => 'optional1',
				'label'         => esc_html__( 'Lead Information 1', 'affiliateniaga' ),
				'required'      => false,
			),
			array(
				'name'          => 'optional2',
				'label'         => esc_html__( 'Lead Information 2', 'affiliateniaga' ),
				'required'      => false,
			),
			array(
				'name'          => 'optional3',
				'label'         => esc_html__( 'Lead Information 3', 'affiliateniaga' ),
				'required'      => false,
			),
		);
	}

    public function mapping_affiliate_id() {
        return array(
            array(
                'name'          => 'affiliate_field',
                'label'         => esc_html__( 'Lead Full Name', 'affiliateniaga' ),
                'required'      => true,
            ),
        );
    }

	/**
	 * Performing a custom action at the end of the form submission process.
	 *
	 * @param array $entry The entry currently being processed.
	 * @param array $form The form currently being processed.
	 */

    public function after_submission( $entry, $form ) {
        // Get form settings
        $settings = $this->get_form_settings( $form );
        $entry_id = $entry['id'];
        $affiliate_id = null;

        // Get the ID of the 'contactStandardFields_full_name' field from form settings
        $name_location = $settings['contactStandardFields_full_name'];
        $email_location = $settings['contactStandardFields_email_address'];
        $optional1 = $settings['contactStandardFields_optional1'];
        $optional2 = $settings['contactStandardFields_optional2'];
        $optional3 = $settings['contactStandardFields_optional3'];
        $affiliate = $settings['affiliate_field'];

        $admin_phone = str_replace('+', '', rgar($entry, $affiliate));
        $affiliate_id = $settings['affiliate_'.$admin_phone];

        // Array to store the locations of full name components
        $full_name_location_array = array();

        // Iterate through form fields to find the full name field and its components
        foreach ( $form['fields'] as $field ) {
            if ( $field->type == 'name' && $field->id == $name_location ) {
                // Store the IDs of components of the full name field
                foreach ($field->inputs as $input) {
                    $full_name_location_array[] = $input['id'];
                }
            }
        }

        // Initialize full name
        $full_name = '';

        // Construct the full name from its components
        foreach( $full_name_location_array as $full_name_location ) {
            $full_name .= ' ' . rgar( $entry, $full_name_location );
        }

        // Trim the full name
        $full_name = trim( $full_name );

        // Prepare the data to be sent in the request
        $data = array(
            'profile' => '1',
            'idev_secret' => '581117986',
            'lead_id' => $entry_id,
            'lead_value' => $entry_id,
            'customer_name' => $full_name,
            'customer_email' => rgar($entry, $email_location),
            'opt1' => rgar($entry, $optional1),
            'opt2' => rgar($entry, $optional2),
            'opt3' => rgar($entry, $optional3),
            'affiliate_id' => $affiliate_id,
        );

        // Convert the array to a string
        $dataString = print_r($data, true);

        // Log the debug information
        $this->log_debug( __METHOD__ . "(): Started for entry id: #" . $dataString );

        // Set up cURL
        $curl = curl_init();

        // Set the URL for the webhook
        $url = 'https://portal.affiliateniaga.com/lead.php';

        // Set up the arguments for the request
        $args = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                // Add any other headers if required
            ),
        );

        // Set cURL options
        curl_setopt_array($curl, $args);

        // Execute cURL request
        $response = curl_exec($curl);

        // Check for errors
        if (curl_errno($curl)) {
            $error_message = curl_error($curl);
            // Handle the error as needed
        } else {
            // Handle the response as needed
            $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        }

        // Close cURL session
        curl_close($curl);
    }




    // # HELPERS -------------------------------------------------------------------------------------------------------

	/**
	 * The feedback callback for the 'mytextbox' setting on the plugin settings page and the 'mytext' setting on the form settings page.
	 *
	 * @param string $value The setting value.
	 *
	 * @return bool
	 */
	public function is_valid_setting( $value ) {
		return strlen( $value ) < 10;
	}

}
