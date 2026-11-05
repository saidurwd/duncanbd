import random, math, sys
W,H=1920,1080
def scene(name, sky, sun, hills, greens, trunk, seed, sunx, haze):
    r=random.Random(seed)
    o=[f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" width="{W}" height="{H}">']
    o.append('<defs>'
      f'<linearGradient id="sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="{sky[0]}"/><stop offset="0.6" stop-color="{sky[1]}"/><stop offset="1" stop-color="{sky[2]}"/></linearGradient>'
      f'<radialGradient id="sun" cx="{sunx/W}" cy="0.36" r="0.55"><stop offset="0" stop-color="{sun}" stop-opacity="0.95"/><stop offset="0.25" stop-color="{sun}" stop-opacity="0.35"/><stop offset="1" stop-color="{sun}" stop-opacity="0"/></radialGradient>'
      f'<linearGradient id="haze" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="{haze}" stop-opacity="0"/><stop offset="1" stop-color="{haze}" stop-opacity="0.55"/></linearGradient>'
      '<filter id="soft"><feGaussianBlur stdDeviation="1.2"/></filter></defs>')
    o.append(f'<rect width="{W}" height="{H}" fill="url(#sky)"/><rect width="{W}" height="{H}" fill="url(#sun)"/>')
    # soft clouds
    for i in range(7):
        cx,cy=r.uniform(0,W),r.uniform(60,300)
        for j in range(5):
            o.append(f'<ellipse cx="{cx+j*r.uniform(40,90):.0f}" cy="{cy+r.uniform(-14,14):.0f}" rx="{r.uniform(120,260):.0f}" ry="{r.uniform(16,36):.0f}" fill="#fff" opacity="{r.uniform(0.05,0.14):.2f}" filter="url(#soft)"/>')
    # distant hills
    for k,(col,base,amp) in enumerate(hills):
        pts=[f'0 {H}']
        for x in range(0,W+40,40):
            y=base+amp*math.sin(x/(260+k*90)+seed+k)+amp*0.5*math.sin(x/97+k*2)
            pts.append(f'{x} {y:.0f}')
        pts.append(f'{W} {H}')
        o.append(f'<polygon points="{" ".join(pts)}" fill="{col}"/>')
    o.append(f'<rect width="{W}" height="{H}" fill="url(#haze)" opacity="0.6"/>')
    # tea terraces: rows of bush mounds from far (y~620) to near (y~1080)
    horizon=600
    rows=34
    for i in range(rows):
        t=i/(rows-1); depth=t**1.7
        y=horizon+(H-horizon+60)*depth
        s=0.18+1.9*depth   # scale
        # path gap in the middle, drifting
        gap=70*s*(1+0.4*math.sin(i/4+seed))
        pc=W*0.5+120*math.sin(i/6+seed)*(1-depth)*1.2
        col=greens[min(len(greens)-1,int(depth*len(greens)))]
        x=-60
        step=26*s
        while x<W+60:
            if abs(x-pc)>gap:
                w=r.uniform(26,40)*s
                h=r.uniform(14,22)*s
                shade=r.uniform(-14,14)
                c=col
                o.append(f'<ellipse cx="{x:.0f}" cy="{y:.0f}" rx="{w:.1f}" ry="{h:.1f}" fill="{c}"/>')
                o.append(f'<ellipse cx="{x-w*0.15:.0f}" cy="{y-h*0.35:.0f}" rx="{w*0.7:.1f}" ry="{h*0.55:.1f}" fill="#fff" opacity="{0.05+0.07*(1-depth):.2f}"/>')
            x+=step
    # path
    o.append(f'<path d="M{W*0.5-30} {horizon} C{W*0.5-90} 760 {W*0.5+60} 900 {W*0.5-120} {H} L{W*0.5+190} {H} C{W*0.5+120} 900 {W*0.5+80} 760 {W*0.5+16} {horizon}Z" fill="{sky[2]}" opacity="0.5"/>')
    # shade trees (silhouettes)
    for i in range(16):
        x=r.uniform(-40,W+40)
        if abs(x-W*0.5)<260: continue
        depth=r.uniform(0,1)**1.2
        base=horizon+40+(H-horizon-40)*depth*0.85
        sc=0.35+1.4*depth
        th=r.uniform(120,210)*sc
        tw=5*sc
        lean=r.uniform(-18,18)*sc
        o.append(f'<path d="M{x-tw:.0f} {base:.0f} L{x+tw:.0f} {base:.0f} L{x+lean+tw*0.4:.0f} {base-th:.0f} L{x+lean-tw*0.4:.0f} {base-th:.0f}Z" fill="{trunk}" opacity="{0.55+0.4*depth:.2f}"/>')
        for j in range(r.randint(5,8)):
            a=r.uniform(0,2*math.pi); rr=r.uniform(0,70)*sc
            o.append(f'<ellipse cx="{x+lean+math.cos(a)*rr*1.4:.0f}" cy="{base-th+math.sin(a)*rr*0.5:.0f}" rx="{r.uniform(45,80)*sc:.0f}" ry="{r.uniform(10,20)*sc:.0f}" fill="{trunk}" opacity="{0.45+0.4*depth:.2f}"/>')
    o.append(f'<rect width="{W}" height="{H}" fill="url(#haze)" opacity="0.25"/>')
    o.append('</svg>')
    open(f'tools/hero/out/{name}.svg','w').write('\n'.join(o))
scene('hero-golden',('#f4c58a','#f6dcae','#f9ecd0'),'#ffe2a8',[('#9bb08a',470,26),('#6f9068',520,22),('#4f7a52',565,18)],['#6aa05a','#4f9150','#3a8046','#2c6c3c','#1f5a33','#17482a'],'#1a2a1c',3,1180,'#f6d9a4')
scene('hero-morning',('#9cc3c9','#cfe2dc','#eef0dc'),'#fff6d8',[('#a9c4b6',480,24),('#7fa592',525,22),('#5d8f74',568,16)],['#79ad78','#5c9c68','#418856','#2f7447','#215f3a','#184d2e'],'#14261b',11,640,'#e3eedd')
scene('hero-dusk',('#6d6f9a','#e9a58a','#f6cf9c'),'#ffd49a',[('#7d7592',470,28),('#59627a',520,22),('#3f5a5c',566,16)],['#4a8a58','#3b7a4c','#2d6a40','#245a36','#1a4a2d','#133b24'],'#101c14',7,1500,'#f1b48e')
