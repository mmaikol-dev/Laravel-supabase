"use client"

import AppLayout from '@/layouts/app-layout'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/react'
import { TrendingUp, PhoneCall, UserCheck, Activity } from "lucide-react"
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from "@/components/ui/chart"
import { Bar, BarChart, CartesianGrid, LabelList, XAxis } from "recharts"

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
]

export default function Dashboard() {
  const chartConfig = {
    total: { label: "Total Calls", color: "var(--chart-1)" },
  } satisfies ChartConfig

  const chartData = [
    { month: "2025-01", total: 120 },
    { month: "2025-02", total: 180 },
    { month: "2025-03", total: 150 },
    { month: "2025-04", total: 210 },
  ]

  const statusSummary = [
    { status: "Answered", totalOrders: 340, totalAmount: 0 },
    { status: "Missed", totalOrders: 48, totalAmount: 0 },
    { status: "Queued", totalOrders: 19, totalAmount: 0 },
    { status: "Ongoing", totalOrders: 7, totalAmount: 0 },
  ]

  const userName = "Agent"

  const dataForChart = chartData.map(item => ({ month: item.month, total: item.total }))

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Call Center Dashboard" />

      <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto bg-white">
        {/* Welcome message */}
        <div className="text-2xl font-semibold mb-4">
          👋 Welcome, {userName}!
        </div>

        {/* Calls Per Month Chart */}
        <div className="grid auto-rows-min gap-4 md:grid-cols-1">
          <Card>
            <CardHeader>
              <CardTitle>Calls Per Month</CardTitle>
              <CardDescription>Total calls handled each month</CardDescription>
            </CardHeader>
            <CardContent className="h-50 md:h-50">
              <ChartContainer config={chartConfig} className="w-full h-full">
                <BarChart
                  data={dataForChart}
                  className="h-full w-full"
                  margin={{ top: 20, right: 20, bottom: 20, left: 0 }}
                >
                  <defs>
                    <linearGradient id="fillTotal" x1="0" y1="0" x2="0" y2="1">
                      <stop offset="5%" stopColor="var(--chart-1)" stopOpacity={0.8} />
                      <stop offset="95%" stopColor="var(--chart-1)" stopOpacity={0.1} />
                    </linearGradient>
                  </defs>

                  <CartesianGrid vertical={false} />
                  <XAxis
                    dataKey="month"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    tickFormatter={(value) => {
                      const date = new Date(value + "-01")
                      return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
                    }}
                  />

                  <ChartTooltip cursor={false} content={<ChartTooltipContent hideLabel />} />

                  <Bar dataKey="total" fill="url(#fillTotal)" radius={8}>
                    <LabelList position="top" offset={12} className="fill-foreground" fontSize={12} />
                  </Bar>
                </BarChart>
              </ChartContainer>
            </CardContent>
          </Card>
        </div>

        {/* Status Summary Filter */}
        <div className="flex gap-2 my-2">
          {['Last 30 Days', 'Last 7 Days', 'Today', 'This Month'].map(period => (
            <button
              key={period}
              className="px-3 py-1 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition"
            >
              {period}
            </button>
          ))}
        </div>

        {/* Status Summary Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          {statusSummary.map((item) => (
            <Card key={item.status} className="border border-gray-200 shadow-sm hover:shadow-md transition-all">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  {item.status === "Answered" && <PhoneCall className="h-5 w-5 text-green-600" />}
                  {item.status === "Missed" && <Activity className="h-5 w-5 text-red-600" />}
                  {item.status === "Queued" && <Activity className="h-5 w-5 text-blue-600" />}
                  {item.status === "Ongoing" && <UserCheck className="h-5 w-5 text-yellow-600" />}
                  {item.status}
                </CardTitle>
                <CardDescription>Calls: {item.totalOrders}</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="text-xl font-semibold">{item.totalOrders} calls</div>
              </CardContent>
              <CardFooter className="flex gap-2 text-sm text-gray-500">
                <TrendingUp className="h-4 w-4" /> Trend info unavailable
              </CardFooter>
            </Card>
          ))}
        </div>
      </div>
    </AppLayout>
  )
}
