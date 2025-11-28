"use client"

import { useState } from "react"
import AppLayout from '@/layouts/app-layout'
import { Head } from '@inertiajs/react'
import { PhoneCall, PhoneOff, User, Volume2, Mic, MicOff, Keypad, Pause, Play, Users2, Clock, MessageSquare } from "lucide-react"
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"

export default function CallCenterDialer() {
  const [muted, setMuted] = useState(false)
  const [speaker, setSpeaker] = useState(false)
  const [onCall, setOnCall] = useState(false)
  const [dialNumber, setDialNumber] = useState("")

  return (
    <AppLayout>
      <Head title="Call Center" />

      <div className="flex h-full flex-col gap-4 p-4 bg-white overflow-x-auto">
        <div className="text-2xl font-semibold mb-2">📞 Call Handling Console</div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
          {/* Dialer Panel */}
          <Card className="lg:col-span-1 shadow-md">
            <CardHeader>
              <CardTitle>Dial Pad</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
              <Input
                placeholder="Enter phone number"
                value={dialNumber}
                onChange={(e) => setDialNumber(e.target.value)}
                className="text-lg p-3"
              />

              {/* Keypad */}
              <div className="grid grid-cols-3 gap-2 text-lg">
                {["1", "2", "3", "4", "5", "6", "7", "8", "9", "*", "0", "#"].map(key => (
                  <Button
                    key={key}
                    variant="outline"
                    onClick={() => setDialNumber(prev => prev + key)}
                    className="h-14 text-xl"
                  >
                    {key}
                  </Button>
                ))}
              </div>

              <Button
                className="bg-green-600 hover:bg-green-700 text-white text-lg py-6"
                onClick={() => setOnCall(true)}
                disabled={onCall || dialNumber.length < 3}
              >
                <PhoneCall className="mr-2" /> Call
              </Button>
            </CardContent>
          </Card>

          {/* Live Call Panel */}
          <Card className="lg:col-span-1 shadow-md">
            <CardHeader>
              <CardTitle>Live Call</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
              {!onCall && (
                <div className="text-gray-500 text-center p-6">
                  Waiting for call to start...
                </div>
              )}

              {onCall && (
                <>
                  <div className="flex flex-col items-center gap-2">
                    <User className="h-12 w-12 text-blue-600" />
                    <div className="text-xl font-semibold">{dialNumber}</div>
                    <div className="text-gray-500">Call Duration: <Clock className="inline h-4 w-4" /> 00:52</div>
                  </div>

                  <div className="grid grid-cols-3 gap-4 mt-4">
                    <Button
                      variant="outline"
                      onClick={() => setMuted(!muted)}
                      className="py-6"
                    >
                      {muted ? <MicOff className="h-6 w-6" /> : <Mic className="h-6 w-6" />}<br />Mute
                    </Button>

                    <Button
                      variant="outline"
                      onClick={() => setSpeaker(!speaker)}
                      className="py-6"
                    >
                      <Volume2 className="h-6 w-6" /><br />Speaker
                    </Button>

                    <Button
                      variant="outline"
                      className="py-6"
                    >
                      <Pause className="h-6 w-6" /><br />Hold
                    </Button>

                    <Button
                      variant="outline"
                      className="py-6"
                    >
                      <Play className="h-6 w-6" /><br />Resume
                    </Button>

                    <Button variant="outline" className="py-6">
                      <Users2 className="h-6 w-6" /><br />Transfer
                    </Button>

                    <Button
                      className="bg-red-600 hover:bg-red-700 text-white py-6 col-span-1"
                      onClick={() => setOnCall(false)}
                    >
                      <PhoneOff className="h-6 w-6" /><br />End
                    </Button>
                  </div>
                </>
              )}
            </CardContent>
          </Card>

          {/* Customer Details + Notes */}
          <Card className="lg:col-span-1 shadow-md">
            <CardHeader>
              <CardTitle>Customer Details & Notes</CardTitle>
            </CardHeader>
            <CardContent className="flex flex-col gap-4">
              <div>
                <div className="font-semibold">Customer Name</div>
                <div className="text-gray-600">Auto‑filled when an incoming call starts</div>
              </div>

              <div>
                <div className="font-semibold">Order History</div>
                <ul className="text-gray-600 list-disc ml-4">
                  <li>Order #4322 — Pending</li>
                  <li>Order #3877 — Delivered</li>
                </ul>
              </div>

              <div>
                <div className="font-semibold">Call Notes</div>
                <Textarea placeholder="Type call notes here..." className="min-h-[120px]" />
              </div>

              <Button className="bg-blue-600 text-white hover:bg-blue-700">Save Notes</Button>
            </CardContent>
          </Card>
        </div>

        {/* Message Quick Actions */}
        <Card className="shadow-md">
          <CardHeader>
            <CardTitle>Quick SMS / WhatsApp Templates</CardTitle>
          </CardHeader>
          <CardContent className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {["Thank you for contacting us", "Your delivery is on the way", "We will call you back shortly"].map(msg => (
              <Button key={msg} variant="outline" className="py-4 flex items-center gap-2">
                <MessageSquare className="h-4 w-4" /> {msg}
              </Button>
            ))}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  )
}

