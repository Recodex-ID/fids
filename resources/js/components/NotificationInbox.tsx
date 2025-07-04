import React, { useState, useEffect } from 'react';
import { useRealTimeFlights } from '../hooks/useRealTimeFlights';

interface Notification {
    id: number;
    type: string;
    message: string;
    priority: 'low' | 'normal' | 'high' | 'urgent';
    status: 'pending' | 'sent' | 'delivered' | 'failed';
    created_at: string;
    read_at?: string;
    flight?: {
        id: number;
        flight_number: string;
        gate?: string;
        status: string;
    };
    metadata?: {
        flight_number?: string;
        old_gate?: string;
        new_gate?: string;
        delay_minutes?: number;
    };
}

interface Props {
    initialNotifications: Notification[];
    passengerId: number;
}

const NotificationInbox: React.FC<Props> = ({ initialNotifications, passengerId }) => {
    const [notifications, setNotifications] = useState<Notification[]>(initialNotifications);
    const [filter, setFilter] = useState<'all' | 'unread' | 'high'>('all');
    const [selectedNotifications, setSelectedNotifications] = useState<number[]>([]);
    
    const { connectionStatus } = useRealTimeFlights();

    useEffect(() => {
        // Listen for real-time notifications
        const echo = (window as any).Echo;
        if (echo) {
            echo.private(`passenger.${passengerId}`)
                .listen('.notification.received', (event: { notification: Notification }) => {
                    setNotifications(prev => [event.notification, ...prev]);
                    
                    // Show browser notification if permission granted
                    if (Notification.permission === 'granted') {
                        new Notification(getNotificationTitle(event.notification), {
                            body: event.notification.message,
                            icon: '/icons/flight-icon.png',
                            tag: `notification-${event.notification.id}`,
                        });
                    }
                });
        }

        return () => {
            if (echo) {
                echo.leaveChannel(`passenger.${passengerId}`);
            }
        };
    }, [passengerId]);

    const getNotificationTitle = (notification: Notification): string => {
        const flightNumber = notification.metadata?.flight_number || notification.flight?.flight_number;
        
        switch (notification.type) {
            case 'gate_change':
                return `Gate Change - ${flightNumber}`;
            case 'boarding_call':
                return `Now Boarding - ${flightNumber}`;
            case 'delay':
                return `Flight Delayed - ${flightNumber}`;
            case 'cancellation':
                return `Flight Cancelled - ${flightNumber}`;
            default:
                return `Flight Update - ${flightNumber}`;
        }
    };

    const getPriorityColor = (priority: string): string => {
        switch (priority) {
            case 'urgent': return 'bg-red-100 border-red-300 text-red-800';
            case 'high': return 'bg-orange-100 border-orange-300 text-orange-800';
            case 'normal': return 'bg-blue-100 border-blue-300 text-blue-800';
            case 'low': return 'bg-gray-100 border-gray-300 text-gray-800';
            default: return 'bg-gray-100 border-gray-300 text-gray-800';
        }
    };

    const getTypeIcon = (type: string): string => {
        switch (type) {
            case 'gate_change': return '🚪';
            case 'boarding_call': return '✈️';
            case 'delay': return '⏰';
            case 'cancellation': return '❌';
            case 'schedule_change': return '📅';
            default: return 'ℹ️';
        }
    };

    const filteredNotifications = notifications.filter(notification => {
        switch (filter) {
            case 'unread':
                return !notification.read_at;
            case 'high':
                return ['high', 'urgent'].includes(notification.priority);
            default:
                return true;
        }
    });

    const markAsRead = async (notificationIds: number[]) => {
        try {
            await fetch(`/passengers/${passengerId}/notifications/mark-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ notification_ids: notificationIds }),
            });

            setNotifications(prev =>
                prev.map(notification =>
                    notificationIds.includes(notification.id)
                        ? { ...notification, read_at: new Date().toISOString() }
                        : notification
                )
            );
        } catch (error) {
            console.error('Failed to mark notifications as read:', error);
        }
    };

    const markAllAsRead = () => {
        const unreadIds = notifications
            .filter(n => !n.read_at)
            .map(n => n.id);
        
        if (unreadIds.length > 0) {
            markAsRead(unreadIds);
        }
    };

    const deleteNotifications = async (notificationIds: number[]) => {
        try {
            await fetch(`/passengers/${passengerId}/notifications`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ notification_ids: notificationIds }),
            });

            setNotifications(prev =>
                prev.filter(notification => !notificationIds.includes(notification.id))
            );
            
            setSelectedNotifications([]);
        } catch (error) {
            console.error('Failed to delete notifications:', error);
        }
    };

    const toggleSelection = (notificationId: number) => {
        setSelectedNotifications(prev =>
            prev.includes(notificationId)
                ? prev.filter(id => id !== notificationId)
                : [...prev, notificationId]
        );
    };

    const selectAll = () => {
        setSelectedNotifications(filteredNotifications.map(n => n.id));
    };

    const clearSelection = () => {
        setSelectedNotifications([]);
    };

    const unreadCount = notifications.filter(n => !n.read_at).length;

    return (
        <div className="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-lg">
            {/* Header */}
            <div className="flex justify-between items-center mb-6">
                <div>
                    <h2 className="text-2xl font-bold text-gray-900">Notifications</h2>
                    <p className="text-gray-600">
                        {unreadCount > 0 ? `${unreadCount} unread notifications` : 'All notifications read'}
                        <span className={`ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs ${
                            connectionStatus === 'connected' 
                                ? 'bg-green-100 text-green-800' 
                                : 'bg-red-100 text-red-800'
                        }`}>
                            {connectionStatus === 'connected' ? '🟢 Live' : '🔴 Offline'}
                        </span>
                    </p>
                </div>
                
                <div className="flex space-x-2">
                    <button
                        onClick={markAllAsRead}
                        disabled={unreadCount === 0}
                        className="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                    >
                        Mark All Read
                    </button>
                </div>
            </div>

            {/* Filters */}
            <div className="flex flex-wrap items-center justify-between mb-6 gap-4">
                <div className="flex space-x-2">
                    {[
                        { key: 'all', label: 'All' },
                        { key: 'unread', label: 'Unread' },
                        { key: 'high', label: 'High Priority' },
                    ].map(({ key, label }) => (
                        <button
                            key={key}
                            onClick={() => setFilter(key as any)}
                            className={`px-4 py-2 text-sm rounded-md transition-colors ${
                                filter === key
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                            }`}
                        >
                            {label}
                        </button>
                    ))}
                </div>

                {selectedNotifications.length > 0 && (
                    <div className="flex items-center space-x-2">
                        <span className="text-sm text-gray-600">
                            {selectedNotifications.length} selected
                        </span>
                        <button
                            onClick={() => markAsRead(selectedNotifications)}
                            className="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                        >
                            Mark Read
                        </button>
                        <button
                            onClick={() => deleteNotifications(selectedNotifications)}
                            className="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
                        >
                            Delete
                        </button>
                        <button
                            onClick={clearSelection}
                            className="px-3 py-1 text-sm bg-gray-400 text-white rounded hover:bg-gray-500"
                        >
                            Clear
                        </button>
                    </div>
                )}
            </div>

            {/* Bulk Actions */}
            {filteredNotifications.length > 0 && (
                <div className="mb-4">
                    <label className="flex items-center space-x-2 text-sm text-gray-600">
                        <input
                            type="checkbox"
                            checked={selectedNotifications.length === filteredNotifications.length}
                            onChange={(e) => e.target.checked ? selectAll() : clearSelection()}
                            className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        />
                        <span>Select all visible notifications</span>
                    </label>
                </div>
            )}

            {/* Notifications List */}
            <div className="space-y-3">
                {filteredNotifications.length === 0 ? (
                    <div className="text-center py-12">
                        <div className="text-6xl mb-4">📬</div>
                        <h3 className="text-lg font-medium text-gray-900 mb-2">No notifications</h3>
                        <p className="text-gray-500">
                            {filter === 'unread' 
                                ? 'All caught up! No unread notifications.'
                                : filter === 'high'
                                ? 'No high priority notifications.'
                                : 'You have no notifications yet.'}
                        </p>
                    </div>
                ) : (
                    filteredNotifications.map((notification) => (
                        <div
                            key={notification.id}
                            className={`p-4 border rounded-lg transition-all hover:shadow-md ${
                                notification.read_at ? 'bg-gray-50' : 'bg-white border-blue-200'
                            } ${selectedNotifications.includes(notification.id) ? 'ring-2 ring-blue-500' : ''}`}
                        >
                            <div className="flex items-start space-x-3">
                                <input
                                    type="checkbox"
                                    checked={selectedNotifications.includes(notification.id)}
                                    onChange={() => toggleSelection(notification.id)}
                                    className="mt-1 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                />
                                
                                <div className="text-2xl">{getTypeIcon(notification.type)}</div>
                                
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center justify-between mb-2">
                                        <h4 className="text-lg font-medium text-gray-900">
                                            {getNotificationTitle(notification)}
                                        </h4>
                                        <div className="flex items-center space-x-2">
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full border ${getPriorityColor(notification.priority)}`}>
                                                {notification.priority.toUpperCase()}
                                            </span>
                                            {!notification.read_at && (
                                                <span className="w-2 h-2 bg-blue-600 rounded-full"></span>
                                            )}
                                        </div>
                                    </div>
                                    
                                    <p className="text-gray-700 mb-3">{notification.message}</p>
                                    
                                    {notification.flight && (
                                        <div className="bg-gray-100 p-3 rounded-md mb-3">
                                            <div className="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                                                <div>
                                                    <span className="font-medium">Flight:</span> {notification.flight.flight_number}
                                                </div>
                                                {notification.flight.gate && (
                                                    <div>
                                                        <span className="font-medium">Gate:</span> {notification.flight.gate}
                                                    </div>
                                                )}
                                                <div>
                                                    <span className="font-medium">Status:</span> {notification.flight.status}
                                                </div>
                                                {notification.metadata?.delay_minutes && (
                                                    <div>
                                                        <span className="font-medium">Delay:</span> {notification.metadata.delay_minutes} min
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                    
                                    <div className="flex items-center justify-between text-sm text-gray-500">
                                        <span>{new Date(notification.created_at).toLocaleString()}</span>
                                        <div className="flex items-center space-x-4">
                                            {notification.flight && (
                                                <a
                                                    href={`/flights/${notification.flight.id}`}
                                                    className="text-blue-600 hover:text-blue-800"
                                                >
                                                    View Flight Details →
                                                </a>
                                            )}
                                            {!notification.read_at && (
                                                <button
                                                    onClick={() => markAsRead([notification.id])}
                                                    className="text-blue-600 hover:text-blue-800"
                                                >
                                                    Mark as Read
                                                </button>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))
                )}
            </div>

            {/* Load More */}
            {filteredNotifications.length >= 20 && (
                <div className="text-center mt-6">
                    <button className="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                        Load More Notifications
                    </button>
                </div>
            )}
        </div>
    );
};

export default NotificationInbox;