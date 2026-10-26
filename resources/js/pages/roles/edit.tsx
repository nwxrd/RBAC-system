import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { index, update } from '@/actions/App/Http/Controllers/RoleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';

type ModuleItem = {
    id: number;
    name: string;
};

type RoleModule = ModuleItem & {
    pivot?: {
        can_view?: boolean | number | string;
        can_create?: boolean | number | string;
        can_update?: boolean | number | string;
        can_delete?: boolean | number | string;
    };
};

type RolePayload = {
    id: number;
    name: string;
    description: string | null;
    modules: RoleModule[];
};

type PermissionFlag = 'can_view' | 'can_create' | 'can_update' | 'can_delete';

type MatrixRow = {
    id: number;
    name: string;
    can_view: boolean;
    can_create: boolean;
    can_update: boolean;
    can_delete: boolean;
};

const flags: { key: PermissionFlag; label: string }[] = [
    { key: 'can_view', label: 'Ver' },
    { key: 'can_create', label: 'Crear' },
    { key: 'can_update', label: 'Editar' },
    { key: 'can_delete', label: 'Eliminar' },
];

function isOn(value: boolean | number | string | undefined): boolean {
    return value === true || value === 1 || value === '1';
}

function initialRows(modules: ModuleItem[], assigned: RoleModule[]): MatrixRow[] {
    return modules.map((module) => {
        const current = assigned.find((item) => item.id === module.id);
        const canView = isOn(current?.pivot?.can_view);

        return {
            id: module.id,
            name: module.name,
            can_view: canView,
            can_create: canView && isOn(current?.pivot?.can_create),
            can_update: canView && isOn(current?.pivot?.can_update),
            can_delete: canView && isOn(current?.pivot?.can_delete),
        };
    });
}

export default function Edit({
    role,
    modules,
}: {
    role: RolePayload;
    modules: ModuleItem[];
}) {
    const [rows, setRows] = useState<MatrixRow[]>(() =>
        initialRows(modules, role.modules),
    );

    function setFlag(moduleId: number, flag: PermissionFlag, checked: boolean) {
        setRows((current) =>
            current.map((row) => {
                if (row.id !== moduleId) {
                    return row;
                }

                if (flag === 'can_view' && !checked) {
                    return {
                        ...row,
                        can_view: false,
                        can_create: false,
                        can_update: false,
                        can_delete: false,
                    };
                }

                return { ...row, [flag]: checked };
            }),
        );
    }

    return (
        <>
            <Head title="Editar rol" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Editar rol"
                    description="Nombre, descripción y permisos por módulo"
                />

                <Card>
                    <CardContent>
                        <Form {...update.form(role.id)} className="space-y-6">
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Nombre</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            defaultValue={role.name}
                                            required
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="description">
                                            Descripción
                                        </Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            defaultValue={role.description ?? ''}
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>

                                    <PermissionMatrix
                                        rows={rows}
                                        onFlag={setFlag}
                                    />

                                    <div className="flex gap-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Guardar
                                        </Button>
                                        <Button variant="secondary" asChild>
                                            <Link href={index.url()}>
                                                Cancelar
                                            </Link>
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function PermissionMatrix({
    rows,
    onFlag,
}: {
    rows: MatrixRow[];
    onFlag: (moduleId: number, flag: PermissionFlag, checked: boolean) => void;
}) {
    return (
        <div className="grid gap-2">
            <Label>Permisos</Label>
            <div className="rounded-xl border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Módulo</TableHead>
                            {flags.map((flag) => (
                                <TableHead key={flag.key}>
                                    {flag.label}
                                </TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.map((row, rowIndex) => (
                            <TableRow key={row.id}>
                                <TableCell>
                                    {row.name}
                                    <input
                                        type="hidden"
                                        name={`permissions[${rowIndex}][module_id]`}
                                        value={row.id}
                                    />
                                </TableCell>
                                {flags.map((flag) => {
                                    const enabled =
                                        flag.key === 'can_view' || row.can_view;

                                    return (
                                        <TableCell key={flag.key}>
                                            <input
                                                type="hidden"
                                                name={`permissions[${rowIndex}][${flag.key}]`}
                                                value="0"
                                            />
                                            <Checkbox
                                                name={
                                                    enabled
                                                        ? `permissions[${rowIndex}][${flag.key}]`
                                                        : undefined
                                                }
                                                value="1"
                                                checked={
                                                    enabled && row[flag.key]
                                                }
                                                disabled={!enabled}
                                                onCheckedChange={(checked) =>
                                                    onFlag(
                                                        row.id,
                                                        flag.key,
                                                        checked === true,
                                                    )
                                                }
                                                aria-label={`${flag.label} ${row.name}`}
                                            />
                                        </TableCell>
                                    );
                                })}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}

Edit.layout = {
    breadcrumbs: [
        {
            title: 'Roles',
            href: index.url(),
        },
        {
            title: 'Editar',
            href: index.url(),
        },
    ],
};
