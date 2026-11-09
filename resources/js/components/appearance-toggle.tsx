import { Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAppearance } from '@/hooks/use-appearance';

// The icon switches with the dark class, not with state, so the server render matches the browser.
export function AppearanceToggle() {
    const { resolvedAppearance, updateAppearance } = useAppearance();

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    aria-label="Switch light or dark mode"
                    onClick={() =>
                        updateAppearance(
                            resolvedAppearance === 'dark' ? 'light' : 'dark',
                        )
                    }
                >
                    <Moon className="size-5 dark:hidden" aria-hidden="true" />
                    <Sun
                        className="hidden size-5 dark:block"
                        aria-hidden="true"
                    />
                </Button>
            </TooltipTrigger>
            <TooltipContent side="bottom">Light or dark mode</TooltipContent>
        </Tooltip>
    );
}
