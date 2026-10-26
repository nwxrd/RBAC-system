import { usePage } from '@inertiajs/react';

type Action = 'can_view' | 'can_create' | 'can_update' | 'can_delete';

interface ModulePermissions {
    can_view: boolean;
    can_create: boolean;
    can_update: boolean;
    can_delete: boolean;
}

interface AuthProps {
    user?: {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        is_super_admin: boolean;
        roles: string[];
        permissions?: Record<string, ModulePermissions>;
    };
}

export function usePermission() {
    const { auth } = usePage<{ auth: AuthProps }>().props;
    const user = auth?.user;
    const isSuperAdmin = Boolean(user?.is_super_admin);
    const permissions = user?.permissions || {};

    const can = (moduleCode: string, action: Action = 'can_view'): boolean => {
        if (isSuperAdmin) return true;
        return Boolean(permissions[moduleCode]?.[action]);
    };

    const canView = (moduleCode: string): boolean => can(moduleCode, 'can_view');

    return {
        can,
        canView,
        isSuperAdmin,
        permissions,
    };
}