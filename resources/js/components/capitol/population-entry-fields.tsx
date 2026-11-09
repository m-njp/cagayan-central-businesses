import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { PopulationPage } from '@/types/population';

type Data = { source_url: string; reason: string };

// What every save of census counts needs: the PSA source, a reason when a verified count changes, and the errors.
export function PopulationEntryFields({
    data,
    errors,
    limits,
    correctsVerified,
    processing,
    changeCount,
    onChange,
}: {
    data: Data;
    errors: Record<string, string>;
    limits: PopulationPage['limits'];
    correctsVerified: boolean;
    processing: boolean;
    changeCount: number;
    onChange: (field: keyof Data, value: string) => void;
}) {
    return (
        <>
            <div className="flex flex-wrap items-end gap-3">
                <Field className="min-w-60 flex-1 gap-1">
                    <FieldLabel htmlFor="population-source" required>
                        PSA Source Link
                    </FieldLabel>
                    <Input
                        id="population-source"
                        type="url"
                        aria-invalid={errors.source_url ? true : undefined}
                        maxLength={limits.sourceUrlMax}
                        value={data.source_url}
                        onChange={(event) =>
                            onChange('source_url', event.target.value)
                        }
                    />
                </Field>
                {correctsVerified && (
                    <Field className="min-w-60 flex-1 gap-1">
                        <FieldLabel htmlFor="population-reason" required>
                            Reason for Correction
                        </FieldLabel>
                        <Input
                            id="population-reason"
                            maxLength={limits.noteMax}
                            value={data.reason}
                            onChange={(event) =>
                                onChange('reason', event.target.value)
                            }
                        />
                    </Field>
                )}
                <Button
                    type="submit"
                    disabled={
                        processing ||
                        changeCount === 0 ||
                        changeCount > limits.changesMax
                    }
                >
                    {processing && <Spinner />}
                    Save for Review
                </Button>
            </div>
            {['changes', 'reason', 'source_url'].map(
                (key) =>
                    errors[key] && (
                        <FieldError key={key}>{errors[key]}</FieldError>
                    ),
            )}
            {Object.keys(errors).some((key) => key.startsWith('changes.')) && (
                <FieldError>
                    Check the highlighted counts: whole numbers only.
                </FieldError>
            )}
        </>
    );
}
