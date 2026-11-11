import type { ComponentProps } from 'react';

export function TaskProgress(props: Omit<ComponentProps<'progress'>, 'className'>) {
    return (
        <progress
            className="h-1 w-full appearance-none [&::-moz-progress-bar]:bg-primary [&::-webkit-progress-bar]:bg-muted [&::-webkit-progress-value]:bg-primary"
            {...props}
        />
    );
}
