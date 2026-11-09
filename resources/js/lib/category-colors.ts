// Palette slots match --category-N in app.css. Class names are written out so Tailwind sees every one.
export const CATEGORY_COLORS = [
    {
        name: 'Blue',
        dot: 'bg-category-1',
        chip: 'bg-category-1/15',
        text: 'text-category-1',
    },
    {
        name: 'Orange',
        dot: 'bg-category-2',
        chip: 'bg-category-2/15',
        text: 'text-category-2',
    },
    {
        name: 'Green',
        dot: 'bg-category-3',
        chip: 'bg-category-3/15',
        text: 'text-category-3',
    },
    {
        name: 'Pink',
        dot: 'bg-category-4',
        chip: 'bg-category-4/15',
        text: 'text-category-4',
    },
    {
        name: 'Violet',
        dot: 'bg-category-5',
        chip: 'bg-category-5/15',
        text: 'text-category-5',
    },
    {
        name: 'Teal',
        dot: 'bg-category-6',
        chip: 'bg-category-6/15',
        text: 'text-category-6',
    },
    {
        name: 'Amber',
        dot: 'bg-category-7',
        chip: 'bg-category-7/15',
        text: 'text-category-7',
    },
    {
        name: 'Red',
        dot: 'bg-category-8',
        chip: 'bg-category-8/15',
        text: 'text-category-8',
    },
    {
        name: 'Lime',
        dot: 'bg-category-9',
        chip: 'bg-category-9/15',
        text: 'text-category-9',
    },
    {
        name: 'Slate',
        dot: 'bg-category-10',
        chip: 'bg-category-10/15',
        text: 'text-category-10',
    },
] as const;

export function categoryColor(slot: number) {
    return CATEGORY_COLORS[(Math.max(1, slot) - 1) % CATEGORY_COLORS.length];
}
