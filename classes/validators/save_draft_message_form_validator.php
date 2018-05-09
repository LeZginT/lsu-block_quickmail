<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    block_quickmail
 * @copyright  2008 onwards Louisiana State University
 * @copyright  2008 onwards Chad Mazilly, Robert Russo, Jason Peak, Dave Elliott, Adam Zapletal, Philip Cali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_quickmail\validators;

use block_quickmail\validators\validator;
use block_quickmail\messenger\body_parser;
use block_quickmail\requests\broadcast_request;
use block_quickmail\requests\compose_request;
use block_quickmail_string;

class save_draft_message_form_validator extends validator {

    /**
     * Defines this specific validator's validation rules
     * 
     * @return void
     */
    public function validator_rules()
    {
        $this->transformed_data = $this->check_extra_params_value('is_broadcast_message', true)
            ? broadcast_request::get_transformed_post_data($this->form_data)
            : compose_request::get_transformed_post_data($this->form_data);

        $this->validate_message_body();

        $this->validate_additional_emails();

        $this->validate_message_type();

        $this->validate_to_send_at();
    }

    /**
     * Checks that the message body exists and that it does not contain any unsupported custom user data keys, adding any errors to the stack
     * 
     * @return void
     */
    private function validate_message_body()
    {
        $body = $this->transformed_data->message;

        $parser = new body_parser($body);

        if ($parser->has_errors()) {
            foreach($parser->errors as $parse_error) {
                $this->add_error($parse_error);
            }
        }
    }

    /**
     * Checks that any and all additional emails requested are valid emails, adding any errors to the stack
     * 
     * @return void
     */
    private function validate_additional_emails()
    {
        //  validate each email value
        foreach ($this->transformed_data->additional_emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
                $this->errors[] = block_quickmail_string::get('invalid_additional_email', $email);
            }
        }
    }

    /**
     * Checks that the selected "message type" is allowed per site config, adding any errors to the stack
     * 
     * @return void
     */
    private function validate_message_type()
    {
        if ( ! in_array($this->form_data->message_type, \block_quickmail_config::get_supported_message_types())) {
            $this->errors[] = block_quickmail_string::get('invalid_send_method');
        }

        $supported_option = $this->get_config('message_types_available');

        if ($supported_option == 'all') {
            return;
        }

        if ($supported_option !== $this->form_data->message_type) {
            $this->errors[] = block_quickmail_string::get('invalid_send_method');
        }
    }

    private function validate_to_send_at()
    {
        //
    }

}