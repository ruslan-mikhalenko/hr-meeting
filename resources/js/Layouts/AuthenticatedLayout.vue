<script setup>
import { ref } from "vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import { Link } from "@inertiajs/vue3";

const showingNavigationDropdown = ref(false);

const isSidebarOpen = ref(false);
/* const isSubmenuOpen = ref(false);  */ // State for the submenu

// State for multiple submenus
const isSubmenuOneOpen = ref(false);
const isSubmenuTwoOpen = ref(false);

// Toggle sidebar visibility
const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};

// Toggle mobile navigation dropdown
const toggleMobileDropdown = () => {
  showingNavigationDropdown.value = !showingNavigationDropdown.value;
};

// Toggle submenu visibility
const toggleSubmenuOne = () => {
  isSubmenuOneOpen.value = !isSubmenuOneOpen.value;
};

const toggleSubmenuTwo = () => {
  isSubmenuTwoOpen.value = !isSubmenuTwoOpen.value;
};
</script>

<template>
  <div class="flex">
    <!-- Sidebar -->
    <div
      :class="[
        'bg-gray-800 text-white h-full shadow-md fixed top-0 left-0 transition-transform duration-300 ease-in-out',
        {
          'transform -translate-x-full': !isSidebarOpen,
          'translate-x-0': isSidebarOpen,
        },
      ]"
      style="width: 250px"
    >
      <div class="p-4">
        <h1 class="text-lg font-semibold mb-4">Меню</h1>
        <ul class="mt-6">
          <!-- Main menu items -->
          <li>
            <div
              class="cursor-pointer flex justify-between items-center py-2 px-3 text-white"
              @click="toggleSubmenuOne"
            >
              <span>Подменю 1</span>
              <span v-if="isSubmenuOneOpen" class="text-sm">&#x2212;</span>
              <!-- Minus sign for open -->
              <span v-else class="text-sm">&#x2B;</span>
              <!-- Plus sign for closed -->
            </div>
            <ul v-show="isSubmenuOneOpen" class="ml-4 mt-1 space-y-1">
              <li>
                <NavLink class="block py-1 px-3 text-white"
                  >Подпункт 1-1</NavLink
                >
              </li>
              <li>
                <NavLink class="block py-1 px-3 text-white"
                  >Подпункт 1-2</NavLink
                >
              </li>
            </ul>
          </li>

          <li>
            <div
              class="cursor-pointer flex justify-between items-center py-2 px-3 text-white"
              @click="toggleSubmenuTwo"
            >
              <span>Подменю 2</span>
              <span v-if="isSubmenuTwoOpen" class="text-sm">&#x2212;</span>
              <!-- Minus sign for open -->
              <span v-else class="text-sm">&#x2B;</span>
              <!-- Plus sign for closed -->
            </div>
            <ul v-show="isSubmenuTwoOpen" class="ml-4 mt-1 space-y-1">
              <li>
                <NavLink class="block py-1 px-3 text-white"
                  >Подпункт 2-1</NavLink
                >
              </li>
              <li>
                <NavLink class="block py-1 px-3 text-white"
                  >Подпункт 2-2</NavLink
                >
              </li>
            </ul>
          </li>

          <!-- Other main menu items can go here -->
        </ul>
      </div>
    </div>

    <!-- Main Content -->
    <div
      :class="[
        'bg-gray-100 flex-grow transition-all duration-300',
        {
          'ml-64': isSidebarOpen,
          'ml-0': !isSidebarOpen,
          'w-full': !isSidebarOpen,
        },
      ]"
    >
      <nav class="bg-white border-b border-gray-200 shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between h-16">
            <div class="flex">
              <!-- Menu Toggle Button for Sidebar -->
              <button
                @click="toggleSidebar"
                class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none transition duration-150 ease-in-out"
              >
                <svg
                  v-if="!isSidebarOpen"
                  class="h-6 w-6"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16m-7 6h7"
                  />
                </svg>
                <svg
                  v-else
                  class="h-6 w-6"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>

              <!-- Logo -->
              <div class="shrink-0 flex items-center">
                <Link :href="route('dashboard')">
                  <ApplicationLogo
                    class="block h-9 w-auto fill-current text-gray-800"
                  />
                </Link>
              </div>

              <!-- Navigation Links -->
              <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                <NavLink
                  v-if="$page.props.auth.user.role === 'super_admin'"
                  :href="route('dashboard')"
                  :active="route().current('dashboard')"
                >
                  Клиенты
                </NavLink>

                <NavLink
                  v-if="$page.props.auth.user.role === 'super_admin'"
                  :href="route('projects.dashboard')"
                  :active="route().current('projects.dashboard')"
                >
                  Проекты
                </NavLink>

                <NavLink
                  v-if="$page.props.auth.user.role === 'super_admin'"
                  :href="route('landings.dashboard')"
                  :active="route().current('landings.dashboard')"
                >
                  Лендинги
                </NavLink>

                <NavLink
                  v-if="$page.props.auth.user.role === 'client'"
                  :href="route('dashboard')"
                  :active="route().current('dashboard')"
                >
                  Проекты
                </NavLink>
              </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
              <div class="ms-3 relative">
                <Dropdown align="right" width="48">
                  <template #trigger>
                    <span class="inline-flex rounded-md">
                      <button
                        type="button"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-400 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                      >
                        {{ $page.props.auth.user.name }}
                        <svg
                          class="ms-2 -me-0.5 h-4 w-4"
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                          />
                        </svg>
                      </button>
                    </span>
                  </template>

                  <template #content>
                    <DropdownLink :href="route('profile.edit')"
                      >Profile</DropdownLink
                    >
                    <DropdownLink
                      :href="route('logout')"
                      method="post"
                      as="button"
                      >Log Out</DropdownLink
                    >
                  </template>
                </Dropdown>
              </div>
            </div>

            <!-- Hamburger for mobile view -->
            <div class="-me-2 flex items-center sm:hidden">
              <button
                @click="toggleMobileDropdown"
                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
              >
                <svg
                  class="h-6 w-6"
                  stroke="currentColor"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <path
                    :class="{
                      hidden: showingNavigationDropdown,
                      'inline-flex': !showingNavigationDropdown,
                    }"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"
                  />
                  <path
                    :class="{
                      hidden: !showingNavigationDropdown,
                      'inline-flex': showingNavigationDropdown,
                    }"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </nav>

      <!-- Responsive Navigation Menu -->
      <div
        :class="{
          block: showingNavigationDropdown,
          hidden: !showingNavigationDropdown,
        }"
        class="sm:hidden"
      >
        <div class="pt-2 pb-3 space-y-1">
          <ResponsiveNavLink
            v-if="$page.props.auth.user.role === 'super_admin'"
            :href="route('dashboard')"
            :active="route().current('dashboard')"
            class="text-gray-700 hover:text-gray-900"
            >Клиенты</ResponsiveNavLink
          >

          <ResponsiveNavLink
            v-if="$page.props.auth.user.role === 'super_admin'"
            :href="route('projects.dashboard')"
            :active="route().current('projects.dashboard')"
            class="text-gray-700 hover:text-gray-900"
            >Проекты</ResponsiveNavLink
          >

          <ResponsiveNavLink
            v-if="$page.props.auth.user.role === 'super_admin'"
            :href="route('landings.dashboard')"
            :active="route().current('landings.dashboard')"
          >
            Лендинги
          </ResponsiveNavLink>

          <ResponsiveNavLink
            v-if="$page.props.auth.user.role === 'client'"
            :href="route('dashboard')"
            :active="route().current('dashboard')"
            class="text-gray-700 hover:text-gray-900"
            >Проекты</ResponsiveNavLink
          >
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
          <div class="px-4">
            <div class="font-medium text-base text-gray-800">
              {{ $page.props.auth.user.name }}
            </div>
            <div class="font-medium text-sm text-gray-500">
              {{ $page.props.auth.user.email }}
            </div>
          </div>
          <div class="mt-3 space-y-1">
            <ResponsiveNavLink :href="route('profile.edit')"
              >Profile</ResponsiveNavLink
            >
            <ResponsiveNavLink :href="route('logout')" method="post" as="button"
              >Log Out</ResponsiveNavLink
            >
          </div>
        </div>
      </div>

      <!-- Page Heading -->
      <header
        :class="{
          'bg-white': $page.props.auth.user.role === 'super_admin',
          'bg-white': $page.props.auth.user.role === 'client',
          'bg-lime-50': $page.props.auth.user.role === 'client',
          shadow: true,
          'mt-0': true,
        }"
        v-if="$slots.header"
      >
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
          <slot name="header" />
        </div>
      </header>

      <!-- Page Content -->
      <main>
        <slot />
      </main>
    </div>
  </div>
</template>
