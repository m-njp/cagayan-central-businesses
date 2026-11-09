import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { DefinitionList } from '@/components/definition-list';
import { FigureRow } from '@/components/figure-row';
import { LazyProvinceMap } from '@/components/lazy-province-map';
import { PageHeader } from '@/components/page-header';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatCount, formatDateTime, NOT_AVAILABLE } from '@/lib/format';
import { pageSearchParams } from '@/lib/navigation';
import { home } from '@/routes';
import { index, store, update } from '@/routes/public/wishes';
import type { SelectOption, WishPage, WishTally } from '@/types/data';

type Props = {
    page: WishPage;
};

export default function SuggestionsIndex({ page }: Props) {
    const params = pageSearchParams(usePage().url);
    const [editing, setEditing] = useState(false);
    const [town, setTown] = useState(
        params.get('town') ?? page.defaultTown ?? '',
    );
    const [type, setType] = useState(params.get('type') ?? '');
    const [specificBusiness, setSpecificBusiness] = useState(
        page.myWish?.specificBusiness ?? '',
    );
    const [counting, setCounting] = useState(false);
    const [countFailed, setCountFailed] = useState(false);
    const [tally, setTally] = useState(page.tally);
    const [showSpecificBusiness, setShowSpecificBusiness] = useState(false);
    const tallyCache = useRef(new Map<string, WishTally | null>());
    const cancelCount = useRef<(() => void) | null>(null);
    const countVersion = useRef(0);
    useEffect(() => () => cancelCount.current?.(), []);
    useEffect(() => {
        tallyCache.current.clear();
        setTally(page.tally);
    }, [page]);
    // Keep the original timer while the visitor changes their selection.
    const [formToken] = useState(page.formToken);

    // The town and type selection lives in the URL.
    const choose = (next: { town: string; type: string }, retry = false) => {
        if (!retry && next.town === town && next.type === type) return;
        setTown(next.town);
        setType(next.type);
        const version = ++countVersion.current;
        cancelCount.current?.();
        cancelCount.current = null;
        setCountFailed(false);
        const query = {
            ...(next.town ? { town: next.town } : {}),
            ...(next.type ? { type: next.type } : {}),
        };
        const key = `${next.town}:${next.type}`;
        if (
            !next.town ||
            !next.type ||
            (!retry && tallyCache.current.has(key))
        ) {
            router.replace({
                url: index({ query }).url,
                preserveState: true,
                preserveScroll: true,
            });
        }
        if (!next.town || !next.type) {
            setTally(null);
            setCounting(false);
        } else if (!retry && tallyCache.current.has(key)) {
            setTally(tallyCache.current.get(key) ?? null);
            setCounting(false);
        } else {
            setTally(null);
            setCounting(true);
            const fail = () => {
                if (version === countVersion.current) setCountFailed(true);
                return false;
            };
            router.get(index(), query, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['tally'],
                async: true,
                showProgress: false,
                onCancelToken: ({ cancel }) => {
                    cancelCount.current = cancel;
                },
                onSuccess: ({ props }) => {
                    if (version !== countVersion.current) return;
                    if (!('tally' in props)) {
                        fail();
                        return;
                    }
                    const result = props.tally as WishTally | null;
                    tallyCache.current.set(key, result);
                    setTally(result);
                },
                onError: fail,
                onHttpException: fail,
                onNetworkError: fail,
                onFinish: () => {
                    if (version === countVersion.current) setCounting(false);
                },
            });
        }
    };

    const wish = page.myWish;
    // During the cooldown the visitor sees their suggestion, not a form they cannot send.
    const showForm = editing || page.nextWishAt === null;
    const formAction = editing && wish ? update.form(wish.id) : store.form();

    return (
        <>
            <Head title="Suggestions" />

            <div className="flex flex-col gap-4 pt-2">
                <Button
                    variant="ghost"
                    size="icon"
                    className="self-start"
                    asChild
                >
                    <Link href={home()} aria-label="Back to Home">
                        <ArrowLeft aria-hidden="true" />
                    </Link>
                </Button>
                <PageHeader title="What Business Do You Wish for Your Town?" />
            </div>

            <Card className="overflow-hidden">
                <CardContent className="grid gap-0 p-0 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
                    <div className="flex flex-col gap-6 p-5 md:p-8">
                        {wish && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Your Suggestion</CardTitle>
                                    <CardDescription>
                                        {[wish.typeName, wish.specificBusiness]
                                            .filter(Boolean)
                                            .join(' and ')}{' '}
                                        in {wish.townName}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <DefinitionList
                                        items={[
                                            {
                                                term: 'Next new suggestion',
                                                description: page.nextWishAt
                                                    ? formatDateTime(
                                                          page.nextWishAt,
                                                      )
                                                    : 'You can send one now',
                                            },
                                            {
                                                term: 'Edits left',
                                                description: wish.nextEditAt
                                                    ? `None until ${formatDateTime(wish.nextEditAt)}`
                                                    : `${wish.editsLeft} of ${page.editsPerWindow} in ${page.editWindowDays} days`,
                                            },
                                        ]}
                                    />
                                </CardContent>
                                {!editing && wish.editsLeft > 0 && (
                                    <CardFooter>
                                        <Button
                                            variant="outline"
                                            onClick={() => {
                                                setEditing(true);
                                                setSpecificBusiness(
                                                    wish.specificBusiness ?? '',
                                                );
                                                setShowSpecificBusiness(
                                                    !!wish.specificBusiness,
                                                );
                                                choose({
                                                    town: wish.townCode,
                                                    type: wish.typeCode ?? '',
                                                });
                                            }}
                                        >
                                            Edit Suggestion
                                        </Button>
                                    </CardFooter>
                                )}
                            </Card>
                        )}

                        {showForm && (
                            <div className="flex flex-col gap-5">
                                {editing && (
                                    <h2 className="type-heading">
                                        Edit Your Suggestion
                                    </h2>
                                )}
                                <Form
                                    key={editing ? 'edit' : 'new'}
                                    {...formAction}
                                    options={{ preserveScroll: true }}
                                    onBefore={() => {
                                        ++countVersion.current;
                                        cancelCount.current?.();
                                        setCounting(false);
                                    }}
                                    onSuccess={() => setEditing(false)}
                                >
                                    {({ processing, errors }) => {
                                        const problem =
                                            errors.wish ??
                                            errors.form_token ??
                                            errors.website;

                                        return (
                                            <FieldGroup className="gap-6">
                                                <Field
                                                    data-invalid={!!errors.town}
                                                >
                                                    <FieldLabel htmlFor="town">
                                                        Town{' '}
                                                        <span aria-hidden="true">
                                                            *
                                                        </span>
                                                    </FieldLabel>
                                                    <OptionSelect
                                                        id="town"
                                                        placeholder="Choose a town"
                                                        options={page.towns}
                                                        value={town}
                                                        invalid={!!errors.town}
                                                        required
                                                        onChange={(value) =>
                                                            choose({
                                                                town: value,
                                                                type,
                                                            })
                                                        }
                                                    />
                                                    <FieldError>
                                                        {errors.town}
                                                    </FieldError>
                                                </Field>

                                                <Field
                                                    data-invalid={!!errors.type}
                                                >
                                                    <FieldLabel htmlFor="type">
                                                        Business Type
                                                    </FieldLabel>
                                                    <OptionSelect
                                                        id="type"
                                                        placeholder="Choose a business type"
                                                        options={page.types}
                                                        value={type}
                                                        invalid={!!errors.type}
                                                        onChange={(value) =>
                                                            choose({
                                                                town,
                                                                type: value,
                                                            })
                                                        }
                                                    />
                                                    <FieldError>
                                                        {errors.type}
                                                    </FieldError>
                                                </Field>

                                                <Button
                                                    type="button"
                                                    variant="link"
                                                    className="h-auto self-start p-0 text-left whitespace-normal"
                                                    aria-expanded={
                                                        showSpecificBusiness
                                                    }
                                                    aria-controls="specific-business-field"
                                                    onClick={() => {
                                                        setShowSpecificBusiness(
                                                            !showSpecificBusiness,
                                                        );
                                                        if (
                                                            showSpecificBusiness
                                                        )
                                                            setSpecificBusiness(
                                                                '',
                                                            );
                                                    }}
                                                >
                                                    {showSpecificBusiness
                                                        ? 'Use Business Type Only'
                                                        : 'Not on the List? Specify a Business'}
                                                </Button>

                                                {showSpecificBusiness && (
                                                    <Field
                                                        id="specific-business-field"
                                                        data-invalid={
                                                            !!errors.specific_business
                                                        }
                                                    >
                                                        <FieldLabel htmlFor="specific_business">
                                                            Specific Business
                                                        </FieldLabel>
                                                        <Input
                                                            id="specific_business"
                                                            name="specific_business"
                                                            value={
                                                                specificBusiness
                                                            }
                                                            maxLength={
                                                                page.specificBusinessMax
                                                            }
                                                            placeholder="Jollibee or Krispy Kreme"
                                                            aria-invalid={
                                                                !!errors.specific_business
                                                            }
                                                            onChange={(event) =>
                                                                setSpecificBusiness(
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                        />
                                                        <FieldError>
                                                            {
                                                                errors.specific_business
                                                            }
                                                        </FieldError>
                                                    </Field>
                                                )}

                                                <Field
                                                    data-invalid={
                                                        !!errors.reason
                                                    }
                                                >
                                                    <FieldLabel htmlFor="reason">
                                                        Why Do You Wish to Have
                                                        This in Your Area?
                                                    </FieldLabel>
                                                    <Textarea
                                                        id="reason"
                                                        name="reason"
                                                        rows={3}
                                                        maxLength={
                                                            page.reasonMax
                                                        }
                                                        defaultValue={
                                                            editing
                                                                ? (wish?.reason ??
                                                                  '')
                                                                : ''
                                                        }
                                                        aria-invalid={
                                                            !!errors.reason
                                                        }
                                                    />
                                                    <FieldError>
                                                        {errors.reason}
                                                    </FieldError>
                                                </Field>

                                                <input
                                                    type="hidden"
                                                    name="form_token"
                                                    value={formToken}
                                                />
                                                {/* Hidden from people and screen readers, so only bots fill it in. */}
                                                <div
                                                    aria-hidden="true"
                                                    className="sr-only"
                                                >
                                                    <label htmlFor="website">
                                                        Website
                                                    </label>
                                                    <input
                                                        id="website"
                                                        name="website"
                                                        tabIndex={-1}
                                                        autoComplete="off"
                                                    />
                                                </div>

                                                {problem && (
                                                    <Alert variant="destructive">
                                                        <AlertDescription>
                                                            {problem}
                                                        </AlertDescription>
                                                    </Alert>
                                                )}

                                                <div className="flex flex-wrap gap-3">
                                                    <Button
                                                        type="submit"
                                                        disabled={processing}
                                                    >
                                                        {processing && (
                                                            <Spinner />
                                                        )}
                                                        {editing
                                                            ? 'Save Changes'
                                                            : 'Send Your Wishes'}
                                                    </Button>
                                                    {editing && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            onClick={() =>
                                                                setEditing(
                                                                    false,
                                                                )
                                                            }
                                                        >
                                                            Cancel
                                                        </Button>
                                                    )}
                                                </div>
                                            </FieldGroup>
                                        );
                                    }}
                                </Form>
                            </div>
                        )}
                    </div>

                    <aside className="flex flex-col gap-6 border-t bg-muted p-5 md:p-8 lg:border-t-0 lg:border-l">
                        <div className="h-72 rounded-lg border bg-background p-4 text-primary md:h-96">
                            <LazyProvinceMap
                                water
                                label="Map of the towns of Cagayan with the chosen town filled"
                                highlight={town ? [town] : []}
                                onSelect={
                                    showForm
                                        ? (code) => choose({ town: code, type })
                                        : undefined
                                }
                                className="size-full"
                            />
                        </div>

                        {(tally || counting || countFailed) && (
                            <section
                                aria-labelledby="count-heading"
                                className="flex flex-col gap-4"
                            >
                                <h2 id="count-heading" className="type-heading">
                                    Suggestions So Far
                                </h2>
                                {counting ? (
                                    <p
                                        role="status"
                                        className="flex items-center gap-2 type-meta"
                                    >
                                        <Spinner /> Updating Count
                                    </p>
                                ) : tally ? (
                                    <>
                                        <FigureRow
                                            figures={[
                                                {
                                                    value: formatCount(
                                                        tally.wishCount,
                                                    ),
                                                    label: 'Suggestions',
                                                },
                                                ...(tally.typeName
                                                    ? [
                                                          {
                                                              value:
                                                                  tally.registeredCount ===
                                                                  null
                                                                      ? NOT_AVAILABLE
                                                                      : formatCount(
                                                                            tally.registeredCount,
                                                                        ),
                                                              label: 'Registered Now',
                                                          },
                                                      ]
                                                    : []),
                                            ]}
                                        />
                                        <p className="type-meta">
                                            {[
                                                tally.typeName,
                                                tally.specificBusiness,
                                            ]
                                                .filter(Boolean)
                                                .join(' and ')}{' '}
                                            in {tally.townName}
                                        </p>
                                    </>
                                ) : (
                                    <div
                                        role="alert"
                                        className="flex flex-col items-start gap-2"
                                    >
                                        <p className="type-body">
                                            The count is not available.
                                        </p>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() =>
                                                choose({ town, type }, true)
                                            }
                                        >
                                            Try Again
                                        </Button>
                                    </div>
                                )}
                            </section>
                        )}
                    </aside>
                </CardContent>
            </Card>
        </>
    );
}

type OptionSelectProps = {
    id: string;
    placeholder: string;
    options: SelectOption[];
    value: string;
    invalid: boolean;
    onChange: (value: string) => void;
    required?: boolean;
};

function OptionSelect({
    id,
    placeholder,
    options,
    value,
    invalid,
    onChange,
    required = false,
}: OptionSelectProps) {
    return (
        <Select
            name={id}
            value={value}
            onValueChange={onChange}
            required={required}
        >
            <SelectTrigger
                id={id}
                className="w-full"
                aria-invalid={invalid}
                aria-required={required}
            >
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
