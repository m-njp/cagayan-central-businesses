import { cn } from '@/lib/utils';

// The official Kita files from docs/brand (identity.html): never retyped, recolored or stretched. A dark surface uses
// the reverse file. Sizes keep the brand minimums: the lockup at least 96 px wide, the mark 32 px.
const FILES = {
    logo: { file: 'kita-logo', width: 375, height: 82 },
    stacked: { file: 'kita-logo-stacked', width: 200, height: 186 },
    mark: { file: 'kita-mark', width: 200, height: 82 },
    icon: { file: 'kita-icon', width: 64, height: 64 },
} as const;

export function KitaLogo({
    variant = 'logo',
    reverse = false,
    label,
    className,
}: {
    variant?: keyof typeof FILES;
    // Reverse only, for a surface that is always dark (the brand footer and panels).
    reverse?: boolean;
    // Set when the logo stands in for the name; otherwise it is decorative.
    label?: string;
    className?: string;
}) {
    const { file, width, height } = FILES[variant];
    const image = (name: string, extra?: string) => (
        <img
            src={`/brand/${name}.svg`}
            alt={label ?? ''}
            width={width}
            height={height}
            className={cn('h-auto shrink-0', extra, className)}
        />
    );

    // The icon is one file on its own ink square, so it reads on any surface.
    if (variant === 'icon') return image(file);
    if (reverse) return image(`${file}-reverse`);

    return (
        <>
            {image(file, 'dark:hidden')}
            {image(`${file}-reverse`, 'hidden dark:block')}
        </>
    );
}
