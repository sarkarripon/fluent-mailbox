// Shared relative-date formatting for list rows and detail views.
// `dateOptions` is passed to toLocaleDateString for dates older than a week.
export const formatRelativeDate = (dateString, dateOptions = { month: 'short', day: 'numeric' }) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) return 'Just now';
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)}d ago`;

    return date.toLocaleDateString(undefined, dateOptions);
};
