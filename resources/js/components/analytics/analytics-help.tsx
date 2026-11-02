import { ChevronRight, CircleHelp } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import type { AnalyticsGuide } from '@/types/economic-performance';

function Group({
    title,
    open,
    children,
}: {
    title: string;
    open?: boolean;
    children: React.ReactNode;
}) {
    return (
        <details open={open} className="group border-t py-1">
            <summary className="flex min-h-10 cursor-pointer list-none items-center gap-2 type-item [&::-webkit-details-marker]:hidden">
                <ChevronRight
                    className="size-4 shrink-0 group-open:rotate-90 motion-safe:transition-transform"
                    aria-hidden="true"
                />
                {title}
            </summary>
            <div className="grid gap-3 pb-3 pl-6 type-body">{children}</div>
        </details>
    );
}

/** Definitions on demand. The measure definitions are authoritative in metrics.md; this repeats them briefly. */
export function AnalyticsHelp({
    guide,
    showBusiness,
}: {
    guide: AnalyticsGuide;
    showBusiness: boolean;
}) {
    const topics = guide.topics.filter(
        (topic) => showBusiness || topic.id !== 'business',
    );
    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button
                    variant="outline"
                    size="icon"
                    className="size-9"
                    aria-label="Help"
                    title="Help"
                >
                    <CircleHelp aria-hidden="true" />
                </Button>
            </SheetTrigger>
            <SheetContent className="w-full overflow-y-auto sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>Reading Government Finances</SheetTitle>
                    <SheetDescription>{guide.introduction}</SheetDescription>
                </SheetHeader>
                <div className="px-4 pb-6">
                    {guide.notes.map((note, index) => (
                        <Group
                            key={note.id}
                            title={note.title}
                            open={index === 0}
                        >
                            <p>{note.text}</p>
                        </Group>
                    ))}
                    <Group title="Measures">
                        {topics.map((topic) => (
                            <section key={topic.id} className="grid gap-2">
                                <h3 className="type-item">{topic.title}</h3>
                                <p className="type-meta">{topic.summary}</p>
                                <dl className="grid gap-2">
                                    {topic.metrics.map((metric) => (
                                        <div key={metric.id}>
                                            <dt>{metric.label}</dt>
                                            <dd className="type-meta">
                                                {metric.meaning}
                                                {metric.formula !==
                                                    'Reported amount.' && (
                                                    <span className="block type-data">
                                                        {metric.formula}
                                                    </span>
                                                )}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            </section>
                        ))}
                    </Group>
                </div>
            </SheetContent>
        </Sheet>
    );
}
