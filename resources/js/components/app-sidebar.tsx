import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, ScrollText, Shield, Users, type LucideIcon } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const moduleIcons: Record<string, LucideIcon> = {
    Shield,
    Users,
    ScrollText,
};

type ModuleNavigation = {
    title: string;
    href: string;
    icon: string;
};

export function AppSidebar() {
    const { navigation = [] } = usePage<{ navigation?: ModuleNavigation[] }>().props;
    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    for (const item of navigation) {
        mainNavItems.push({
            title: item.title,
            href: item.href,
            icon: moduleIcons[item.icon] ?? LayoutGrid,
        });
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
