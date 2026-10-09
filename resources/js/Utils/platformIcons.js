// Single source of truth for platform pillar icons, shared by the public pages and the admin.
// The keys must match PlatformPillarController::ICONS.
import {
  AcademicCapIcon,
  BanknotesIcon,
  BoltIcon,
  BookOpenIcon,
  BriefcaseIcon,
  BuildingLibraryIcon,
  BuildingOffice2Icon,
  ChartBarIcon,
  GlobeAltIcon,
  HeartIcon,
  HomeIcon,
  LightBulbIcon,
  ScaleIcon,
  ShieldCheckIcon,
  SparklesIcon,
  TruckIcon,
  UserGroupIcon,
  WrenchIcon,
} from '@heroicons/vue/24/outline'

export const platformIcons = {
  heart: { component: HeartIcon, label: 'Health & care' },
  'academic-cap': { component: AcademicCapIcon, label: 'Education' },
  'book-open': { component: BookOpenIcon, label: 'Learning' },
  briefcase: { component: BriefcaseIcon, label: 'Jobs & business' },
  banknotes: { component: BanknotesIcon, label: 'Economy' },
  'chart-bar': { component: ChartBarIcon, label: 'Growth' },
  home: { component: HomeIcon, label: 'Housing' },
  'building-office-2': { component: BuildingOffice2Icon, label: 'Urban development' },
  'building-library': { component: BuildingLibraryIcon, label: 'Government' },
  scale: { component: ScaleIcon, label: 'Justice' },
  'shield-check': { component: ShieldCheckIcon, label: 'Security' },
  'user-group': { component: UserGroupIcon, label: 'Community' },
  bolt: { component: BoltIcon, label: 'Energy & renewal' },
  truck: { component: TruckIcon, label: 'Transport' },
  'globe-alt': { component: GlobeAltIcon, label: 'Environment' },
  'light-bulb': { component: LightBulbIcon, label: 'Innovation' },
  wrench: { component: WrenchIcon, label: 'Infrastructure' },
  sparkles: { component: SparklesIcon, label: 'Culture' },
}

export const getPlatformIcon = (name) => platformIcons[name]?.component || BriefcaseIcon
