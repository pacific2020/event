
export const formatDate = (dateString: string | null | undefined): string => {
  if (!dateString) return '—';

  const date = new Date(dateString);

  if (isNaN(date.getTime())) return 'Invalid Date';

return date.toLocaleString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false, // This removes AM/PM and uses 24-hour time
  });
};