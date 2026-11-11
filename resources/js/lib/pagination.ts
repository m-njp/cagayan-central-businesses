export function paginate<T>(rows: T[], page: number, size: number) {
    const lastPage = Math.max(1, Math.ceil(rows.length / size));
    const current = Math.min(page, lastPage);
    const start = (current - 1) * size;
    return {
        rows: rows.slice(start, start + size),
        page: current,
        lastPage,
        total: rows.length,
    };
}
