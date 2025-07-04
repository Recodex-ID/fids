<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Flight Status Change Templates
            [
                'name' => 'flight_status_change_email',
                'type' => 'flight_status_change',
                'channel' => 'mail',
                'subject' => 'Flight {{flight_number}} Status Update',
                'content' => 'Dear {{passenger_name}}, your flight {{flight_number}} from {{origin_city}} to {{destination_city}} status has been updated. Please check your booking for details.',
                'html_content' => '<h2>Flight Status Update</h2><p>Dear {{passenger_name}},</p><p>Your flight <strong>{{flight_number}}</strong> from {{origin_city}} to {{destination_city}} status has been updated.</p><p>Please check your booking for the latest details.</p>',
                'description' => 'General flight status change notification for email',
            ],
            [
                'name' => 'flight_status_change_sms',
                'type' => 'flight_status_change',
                'channel' => 'sms',
                'content' => 'Flight {{flight_number}} status updated. Check your booking for details.',
                'description' => 'SMS notification for flight status changes',
            ],
            
            // Gate Change Templates
            [
                'name' => 'gate_change_email',
                'type' => 'gate_change',
                'channel' => 'mail',
                'subject' => 'Gate Change: Flight {{flight_number}}',
                'content' => 'Dear {{passenger_name}}, your flight {{flight_number}} gate has changed from {{old_gate}} to {{new_gate}}. Please proceed to the new gate.',
                'html_content' => '<h2>Gate Change Notice</h2><p>Dear {{passenger_name}},</p><p>Your flight <strong>{{flight_number}}</strong> gate has changed:</p><ul><li>Old Gate: {{old_gate}}</li><li>New Gate: <strong>{{new_gate}}</strong></li></ul><p>Please proceed to the new gate immediately.</p>',
                'description' => 'Gate change notification for email',
            ],
            [
                'name' => 'gate_change_sms',
                'type' => 'gate_change',
                'channel' => 'sms',
                'content' => 'GATE CHANGE: Flight {{flight_number}} moved to gate {{new_gate}}. Previous gate: {{old_gate}}.',
                'description' => 'SMS notification for gate changes',
            ],
            
            // Boarding Call Templates
            [
                'name' => 'boarding_call_email',
                'type' => 'boarding_call',
                'channel' => 'mail',
                'subject' => 'Now Boarding: Flight {{flight_number}}',
                'content' => 'Dear {{passenger_name}}, your flight {{flight_number}} is now boarding at gate {{gate}}. Please proceed to the gate with your boarding pass.',
                'html_content' => '<h2>Now Boarding</h2><p>Dear {{passenger_name}},</p><p>Your flight <strong>{{flight_number}}</strong> is now boarding at gate <strong>{{gate}}</strong>.</p><p>Please proceed to the gate with your boarding pass and valid ID.</p><p>Seat: {{seat_number}}</p>',
                'description' => 'Boarding call notification for email',
            ],
            [
                'name' => 'boarding_call_sms',
                'type' => 'boarding_call',
                'channel' => 'sms',
                'content' => 'NOW BOARDING: Flight {{flight_number}} at gate {{gate}}. Seat {{seat_number}}. Please proceed to gate immediately.',
                'description' => 'SMS notification for boarding calls',
            ],
            [
                'name' => 'boarding_call_push',
                'type' => 'boarding_call',
                'channel' => 'push',
                'subject' => 'Now Boarding - {{flight_number}}',
                'content' => 'Your flight is now boarding at gate {{gate}}. Seat {{seat_number}}.',
                'description' => 'Push notification for boarding calls',
            ],
            
            // Delay Templates
            [
                'name' => 'delay_email',
                'type' => 'delay',
                'channel' => 'mail',
                'subject' => 'Flight Delay: {{flight_number}}',
                'content' => 'Dear {{passenger_name}}, your flight {{flight_number}} has been delayed by {{delay_minutes}} minutes. New departure time: {{new_departure_time}}.',
                'html_content' => '<h2>Flight Delay Notice</h2><p>Dear {{passenger_name}},</p><p>We regret to inform you that flight <strong>{{flight_number}}</strong> has been delayed by <strong>{{delay_minutes}} minutes</strong>.</p><p>New departure time: <strong>{{new_departure_time}}</strong></p><p>We apologize for any inconvenience caused.</p>',
                'description' => 'Flight delay notification for email',
            ],
            [
                'name' => 'delay_sms',
                'type' => 'delay',
                'channel' => 'sms',
                'content' => 'DELAY: Flight {{flight_number}} delayed by {{delay_minutes}} minutes. New departure: {{new_departure_time}}.',
                'description' => 'SMS notification for flight delays',
            ],
            
            // Cancellation Templates
            [
                'name' => 'cancellation_email',
                'type' => 'cancellation',
                'channel' => 'mail',
                'subject' => 'Flight Cancelled: {{flight_number}}',
                'content' => 'Dear {{passenger_name}}, we regret to inform you that flight {{flight_number}} has been cancelled. Please contact customer service for rebooking options.',
                'html_content' => '<h2>Flight Cancellation</h2><p>Dear {{passenger_name}},</p><p>We regret to inform you that flight <strong>{{flight_number}}</strong> from {{origin_city}} to {{destination_city}} has been cancelled.</p><p>Please contact our customer service team for rebooking options and compensation details.</p><p>We sincerely apologize for this inconvenience.</p>',
                'description' => 'Flight cancellation notification for email',
            ],
            [
                'name' => 'cancellation_sms',
                'type' => 'cancellation',
                'channel' => 'sms',
                'content' => 'CANCELLED: Flight {{flight_number}} has been cancelled. Contact customer service for rebooking. Ref: {{booking_reference}}',
                'description' => 'SMS notification for flight cancellations',
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
