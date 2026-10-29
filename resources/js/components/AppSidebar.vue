<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Building2, LayoutGrid, PawPrint } from '@lucide/vue';
import { index as animals } from '@/routes/animals';
import { computed } from 'vue';
import { index as organizations } from '@/routes/organizations';
import { edit as tenantOrganization } from '@/routes/tenant/organization';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
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

const page = usePage();
const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Przegląd',
        href: dashboard(),
        icon: LayoutGrid,
    },
    ...(page.props.auth.canManageOrganizations
        ? [{ title: 'Organizacje', href: organizations(), icon: Building2 }]
        : []),
    ...(page.props.auth.canManageAnimals
        ? [{ title: 'Zwierzęta', href: animals(), icon: PawPrint }]
        : []),
    ...(page.props.auth.canManageOrganization
        ? [
              {
                  title: 'Moja organizacja',
                  href: tenantOrganization(),
                  icon: Building2,
              },
          ]
        : []),
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
