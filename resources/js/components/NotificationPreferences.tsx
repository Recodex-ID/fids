import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';

interface NotificationPreferences {
    id?: number;
    email_enabled: boolean;
    sms_enabled: boolean;
    push_enabled: boolean;
    in_app_enabled: boolean;
    flight_status_changes: boolean;
    gate_changes: boolean;
    boarding_calls: boolean;
    delays: boolean;
    cancellations: boolean;
    schedule_changes: boolean;
    check_in_reminders: boolean;
    baggage_updates: boolean;
    boarding_call_advance_minutes: number;
    delay_notification_threshold: number;
    quiet_hours_start: string;
    quiet_hours_end: string;
    notification_frequency: 'immediate' | 'batched' | 'summary';
    language: string;
    timezone: string;
}

interface Props {
    preferences: NotificationPreferences;
    passenger: {
        id: number;
        name: string;
        email: string;
        phone?: string;
    };
}

const NotificationPreferences: React.FC<Props> = ({ preferences, passenger }) => {
    const [isRequestingPermission, setIsRequestingPermission] = useState(false);
    const [pushPermissionStatus, setPushPermissionStatus] = useState<'default' | 'granted' | 'denied'>('default');

    const { data, setData, patch, processing, errors, reset } = useForm<NotificationPreferences>(preferences);

    useEffect(() => {
        // Check current push notification permission status
        if ('Notification' in window) {
            setPushPermissionStatus(Notification.permission);
        }
    }, []);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(`/passengers/${passenger.id}/notification-preferences`, {
            onSuccess: () => {
                // Show success message
                alert('Notification preferences updated successfully!');
            },
        });
    };

    const requestPushPermission = async () => {
        if (!('Notification' in window)) {
            alert('This browser does not support push notifications');
            return;
        }

        setIsRequestingPermission(true);
        
        try {
            const permission = await Notification.requestPermission();
            setPushPermissionStatus(permission);
            
            if (permission === 'granted') {
                // Register service worker and create push subscription
                await registerPushNotifications();
            }
        } catch (error) {
            console.error('Error requesting push permission:', error);
        } finally {
            setIsRequestingPermission(false);
        }
    };

    const registerPushNotifications = async () => {
        try {
            const registration = await navigator.serviceWorker.register('/sw.js');
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: 'your-vapid-public-key' // Replace with actual VAPID key
            });

            // Send subscription to server
            await fetch(`/passengers/${passenger.id}/push-subscription`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    p256dh: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('p256dh')!))),
                    auth: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('auth')!))),
                }),
            });
        } catch (error) {
            console.error('Error registering push notifications:', error);
        }
    };

    return (
        <div className="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-lg">
            <div className="mb-8">
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Notification Preferences</h2>
                <p className="text-gray-600">
                    Manage how and when you receive flight notifications for {passenger.name}
                </p>
            </div>

            <form onSubmit={handleSubmit} className="space-y-8">
                {/* Notification Channels */}
                <div className="bg-gray-50 p-6 rounded-lg">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Notification Channels</h3>
                    <div className="space-y-4">
                        {/* Email */}
                        <label className="flex items-center space-x-3">
                            <input
                                type="checkbox"
                                checked={data.email_enabled}
                                onChange={(e) => setData('email_enabled', e.target.checked)}
                                className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            />
                            <div>
                                <span className="font-medium text-gray-900">Email Notifications</span>
                                <p className="text-sm text-gray-500">Send notifications to {passenger.email}</p>
                            </div>
                        </label>

                        {/* SMS */}
                        <label className="flex items-center space-x-3">
                            <input
                                type="checkbox"
                                checked={data.sms_enabled}
                                onChange={(e) => setData('sms_enabled', e.target.checked)}
                                disabled={!passenger.phone}
                                className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 disabled:opacity-50"
                            />
                            <div>
                                <span className="font-medium text-gray-900">SMS Notifications</span>
                                <p className="text-sm text-gray-500">
                                    {passenger.phone ? `Send SMS to ${passenger.phone}` : 'No phone number provided'}
                                </p>
                            </div>
                        </label>

                        {/* Push Notifications */}
                        <div className="flex items-center space-x-3">
                            <input
                                type="checkbox"
                                checked={data.push_enabled && pushPermissionStatus === 'granted'}
                                onChange={(e) => setData('push_enabled', e.target.checked)}
                                disabled={pushPermissionStatus !== 'granted'}
                                className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 disabled:opacity-50"
                            />
                            <div className="flex-1">
                                <span className="font-medium text-gray-900">Push Notifications</span>
                                <p className="text-sm text-gray-500">
                                    Receive notifications even when the browser is closed
                                </p>
                            </div>
                            {pushPermissionStatus !== 'granted' && (
                                <button
                                    type="button"
                                    onClick={requestPushPermission}
                                    disabled={isRequestingPermission}
                                    className="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {isRequestingPermission ? 'Requesting...' : 'Enable'}
                                </button>
                            )}
                        </div>

                        {/* In-App */}
                        <label className="flex items-center space-x-3">
                            <input
                                type="checkbox"
                                checked={data.in_app_enabled}
                                onChange={(e) => setData('in_app_enabled', e.target.checked)}
                                className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            />
                            <div>
                                <span className="font-medium text-gray-900">In-App Notifications</span>
                                <p className="text-sm text-gray-500">Show notifications in the application</p>
                            </div>
                        </label>
                    </div>
                </div>

                {/* Notification Types */}
                <div className="bg-gray-50 p-6 rounded-lg">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Notification Types</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {[
                            { key: 'flight_status_changes', label: 'Flight Status Changes', desc: 'When your flight status is updated' },
                            { key: 'gate_changes', label: 'Gate Changes', desc: 'When your departure gate changes' },
                            { key: 'boarding_calls', label: 'Boarding Calls', desc: 'When your flight is ready for boarding' },
                            { key: 'delays', label: 'Flight Delays', desc: 'When your flight is delayed' },
                            { key: 'cancellations', label: 'Cancellations', desc: 'When your flight is cancelled' },
                            { key: 'schedule_changes', label: 'Schedule Changes', desc: 'When departure/arrival times change' },
                            { key: 'check_in_reminders', label: 'Check-in Reminders', desc: 'Reminders to check in online' },
                            { key: 'baggage_updates', label: 'Baggage Updates', desc: 'Updates about your baggage status' },
                        ].map(({ key, label, desc }) => (
                            <label key={key} className="flex items-start space-x-3">
                                <input
                                    type="checkbox"
                                    checked={data[key as keyof NotificationPreferences] as boolean}
                                    onChange={(e) => setData(key as keyof NotificationPreferences, e.target.checked)}
                                    className="w-4 h-4 mt-1 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                />
                                <div>
                                    <span className="font-medium text-gray-900">{label}</span>
                                    <p className="text-sm text-gray-500">{desc}</p>
                                </div>
                            </label>
                        ))}
                    </div>
                </div>

                {/* Timing Preferences */}
                <div className="bg-gray-50 p-6 rounded-lg">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Timing Preferences</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Boarding Call Advance (minutes)
                            </label>
                            <input
                                type="number"
                                min="10"
                                max="120"
                                value={data.boarding_call_advance_minutes}
                                onChange={(e) => setData('boarding_call_advance_minutes', parseInt(e.target.value))}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p className="text-sm text-gray-500 mt-1">
                                How many minutes before boarding to send notifications
                            </p>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Delay Threshold (minutes)
                            </label>
                            <input
                                type="number"
                                min="5"
                                max="180"
                                value={data.delay_notification_threshold}
                                onChange={(e) => setData('delay_notification_threshold', parseInt(e.target.value))}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                            <p className="text-sm text-gray-500 mt-1">
                                Minimum delay time to trigger notifications
                            </p>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Quiet Hours Start
                            </label>
                            <input
                                type="time"
                                value={data.quiet_hours_start}
                                onChange={(e) => setData('quiet_hours_start', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Quiet Hours End
                            </label>
                            <input
                                type="time"
                                value={data.quiet_hours_end}
                                onChange={(e) => setData('quiet_hours_end', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                </div>

                {/* Advanced Settings */}
                <div className="bg-gray-50 p-6 rounded-lg">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Advanced Settings</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Notification Frequency
                            </label>
                            <select
                                value={data.notification_frequency}
                                onChange={(e) => setData('notification_frequency', e.target.value as 'immediate' | 'batched' | 'summary')}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="immediate">Immediate</option>
                                <option value="batched">Batched (every 30 minutes)</option>
                                <option value="summary">Daily Summary</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Language
                            </label>
                            <select
                                value={data.language}
                                onChange={(e) => setData('language', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="en">English</option>
                                <option value="es">Español</option>
                                <option value="fr">Français</option>
                                <option value="de">Deutsch</option>
                                <option value="it">Italiano</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Submit Button */}
                <div className="flex justify-end space-x-4">
                    <button
                        type="button"
                        onClick={() => reset()}
                        className="px-6 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:ring-2 focus:ring-gray-500"
                    >
                        Reset
                    </button>
                    <button
                        type="submit"
                        disabled={processing}
                        className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                        {processing ? 'Saving...' : 'Save Preferences'}
                    </button>
                </div>
            </form>

            {/* Error Messages */}
            {Object.keys(errors).length > 0 && (
                <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                    <h4 className="text-sm font-medium text-red-800 mb-2">Please fix the following errors:</h4>
                    <ul className="text-sm text-red-700 list-disc pl-5">
                        {Object.values(errors).map((error, index) => (
                            <li key={index}>{error}</li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
};

export default NotificationPreferences;