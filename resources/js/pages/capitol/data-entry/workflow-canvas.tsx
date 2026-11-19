import {
    Background,
    BackgroundVariant,
    Controls,
    Handle,
    MarkerType,
    Position,
    ReactFlow,
} from '@xyflow/react';
import type { Edge, Node, NodeProps } from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { useMemo } from 'react';
import { useAppearance } from '@/hooks/use-appearance';
import { icons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import type { Workflow } from './workflow-definitions';

type StepNode = Node<
    {
        label: string;
        icon: keyof typeof icons;
        active: boolean;
        onSelect: () => void;
    },
    'step'
>;

const stepIcons: Partial<Record<string, keyof typeof icons>> = {
    choose: 'work',
    report: 'document',
    enter: 'edit',
    upload: 'document',
    census: 'population',
    record: 'businesses',
    draft: 'document',
    pending: 'search',
    verified: 'parsed',
    returned: 'reset',
    save: 'parsed',
};
const hiddenHandle = { visibility: 'hidden' } as const;
const returnHandle = { ...hiddenHandle, top: '24%' };
const reviewHandle = { ...hiddenHandle, top: '75%' };

function WorkflowStep({ data }: NodeProps<StepNode>) {
    const Icon = icons[data.icon];
    return (
        <>
            <Handle
                type="target"
                position={Position.Top}
                id="top"
                style={hiddenHandle}
            />
            <Handle
                type="target"
                position={Position.Right}
                id="right"
                style={hiddenHandle}
            />
            <Handle
                type="target"
                position={Position.Left}
                id="review-in"
                style={reviewHandle}
            />
            <Handle
                type="target"
                position={Position.Right}
                id="return-in"
                style={returnHandle}
            />
            <button
                type="button"
                aria-current={data.active ? 'step' : undefined}
                onClick={data.onSelect}
                className={cn(
                    'nodrag nopan pointer-events-auto flex min-h-14 w-full items-center gap-2 rounded-sm border px-2 py-1.5 text-left text-sm/tight font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring motion-reduce:transition-none',
                    data.active
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border bg-card text-card-foreground hover:border-primary hover:bg-accent',
                )}
            >
                <Icon aria-hidden="true" className="size-4 shrink-0" />
                {data.label}
            </button>
            <Handle
                type="source"
                position={Position.Bottom}
                id="bottom"
                style={hiddenHandle}
            />
            <Handle
                type="source"
                position={Position.Left}
                id="left"
                style={hiddenHandle}
            />
            <Handle
                type="source"
                position={Position.Right}
                id="review-out"
                style={reviewHandle}
            />
            <Handle
                type="source"
                position={Position.Left}
                id="return-out"
                style={returnHandle}
            />
        </>
    );
}

const nodeTypes = { step: WorkflowStep };
const fitOptions = { padding: 0.08, maxZoom: 1.1 };
const defaultEdgeOptions = {
    type: 'smoothstep',
    markerEnd: { type: MarkerType.ArrowClosed },
    labelStyle: { fill: 'var(--foreground)', fontSize: 10, fontWeight: 500 },
    labelBgStyle: { fill: 'var(--background)' },
};

export default function WorkflowCanvas({
    workflow,
    selected,
    onSelect,
}: {
    workflow: Workflow;
    selected: string;
    onSelect: (index: number) => void;
}) {
    const { resolvedAppearance } = useAppearance();
    const nodes: StepNode[] = useMemo(
        () =>
            workflow.steps.map((step, index) => ({
                id: step.id,
                type: 'step',
                position: { x: (step.column ?? 0) * 222, y: step.row * 96 },
                width: 152,
                data: {
                    label: step.label,
                    icon: stepIcons[step.id] ?? 'dataEntry',
                    active: step.id === selected,
                    onSelect: () => onSelect(index),
                },
            })),
        [workflow, selected, onSelect],
    );
    const edges: Edge[] = useMemo(
        () =>
            workflow.connections.map(([source, target]) => {
                const resubmit = source === 'returned';
                const returned = target === 'returned';
                const draft = source === 'draft';
                const active = source === selected || target === selected;
                return {
                    id: `${source}-${target}`,
                    source,
                    target,
                    sourceHandle: resubmit
                        ? 'return-out'
                        : returned
                          ? 'review-out'
                          : draft
                            ? 'left'
                            : 'bottom',
                    targetHandle: resubmit
                        ? 'return-in'
                        : returned
                          ? 'review-in'
                          : draft
                            ? 'right'
                            : 'top',
                    label: resubmit
                        ? 'Resubmit'
                        : returned
                          ? 'Return'
                          : undefined,
                    markerEnd: active
                        ? {
                              type: MarkerType.ArrowClosed,
                              color: 'var(--primary)',
                          }
                        : undefined,
                    style: {
                        stroke: active ? 'var(--primary)' : undefined,
                        strokeWidth: active ? 2 : undefined,
                        strokeDasharray: resubmit ? '4 3' : undefined,
                    },
                };
            }),
        [workflow, selected],
    );

    return (
        <div className="h-[23rem] min-w-0 rounded-sm border border-foreground/15 bg-background/55 lg:h-[27rem]">
            <ReactFlow
                aria-label="Interactive data entry workflow"
                className="[--xy-background-color:transparent] [--xy-edge-stroke-width:1.5] [--xy-edge-stroke:var(--muted-foreground)]"
                nodes={nodes}
                edges={edges}
                nodeTypes={nodeTypes}
                colorMode={resolvedAppearance}
                defaultMarkerColor={null}
                defaultEdgeOptions={defaultEdgeOptions}
                fitView
                fitViewOptions={fitOptions}
                minZoom={0.35}
                maxZoom={2}
                nodesDraggable={false}
                nodesConnectable={false}
                edgesReconnectable={false}
                nodesFocusable={false}
                edgesFocusable={false}
                elementsSelectable={false}
                panOnDrag={false}
                zoomOnScroll={false}
                zoomOnDoubleClick={false}
                zoomOnPinch={false}
                preventScrolling={false}
            >
                <Background
                    variant={BackgroundVariant.Dots}
                    gap={15}
                    size={0.9}
                    color="color-mix(in oklab, var(--foreground) 24%, transparent)"
                />
                <Controls
                    orientation="horizontal"
                    showInteractive={false}
                    fitViewOptions={fitOptions}
                />
            </ReactFlow>
        </div>
    );
}
