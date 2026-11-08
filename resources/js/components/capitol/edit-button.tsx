import { Button } from '@/components/ui/button';
import { icons } from '@/lib/icons';

// The row action for editing or correcting one record: a pen, named for screen readers and the hover title.
export function EditButton({
    label,
    onClick,
}: {
    label: string;
    onClick: () => void;
}) {
    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            className="relative z-10 size-8"
            aria-label={label}
            title={label}
            onClick={onClick}
        >
            <icons.edit className="size-4" aria-hidden="true" />
        </Button>
    );
}
